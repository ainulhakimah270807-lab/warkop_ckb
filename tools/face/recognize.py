"""Local YuNet detection and SFace embedding. Images never leave this server."""
import argparse
import json
from pathlib import Path

import cv2
import numpy as np


def embedding(image_path, models):
    image = cv2.imdecode(np.fromfile(image_path, dtype=np.uint8), cv2.IMREAD_COLOR)
    if image is None:
        raise ValueError('Foto tidak dapat dibaca. Gunakan foto JPEG atau PNG.')
    h, w = image.shape[:2]
    if max(h, w) > 1600:
        image = cv2.resize(image, (round(w * 1600 / max(h, w)), round(h * 1600 / max(h, w))))
    h, w = image.shape[:2]
    detector = cv2.FaceDetectorYN.create(str(models / 'yunet.onnx'), '', (w, h), 0.9, 0.3, 5000)
    _, faces = detector.detect(image)
    if faces is None or len(faces) != 1:
        raise ValueError('Foto harus menampilkan tepat satu wajah. Ambil ulang foto.')
    face = faces[0]
    if min(face[2], face[3]) < 80:
        raise ValueError('Wajah terlalu jauh. Dekatkan wajah ke kamera.')
    recognizer = cv2.FaceRecognizerSF.create(str(models / 'sface.onnx'), '')
    aligned = recognizer.alignCrop(image, face)
    if cv2.Laplacian(cv2.cvtColor(aligned, cv2.COLOR_BGR2GRAY), cv2.CV_64F).var() < 20:
        raise ValueError('Foto wajah buram. Pegang kamera dengan stabil dan ambil ulang.')
    return recognizer.feature(aligned).flatten().tolist()


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--models', type=Path, required=True)
    parser.add_argument('--image', required=True)
    args = parser.parse_args()
    try:
        print(json.dumps({'embedding': embedding(args.image, args.models)}))
    except ValueError as error:
        print(json.dumps({'error': str(error)}))
