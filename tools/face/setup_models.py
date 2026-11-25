"""Download pinned OpenCV Zoo models and verify their Git LFS SHA-256."""
import argparse
import hashlib
from pathlib import Path
import urllib.request

REVISION = '48b10fa505aeb51fbd127fc60a5c660040e823b7'
MODELS = {
    'yunet.onnx': 'models/face_detection_yunet/face_detection_yunet_2023mar.onnx',
    'sface.onnx': 'models/face_recognition_sface/face_recognition_sface_2021dec.onnx',
}


def setup(destination):
    destination.mkdir(parents=True, exist_ok=True)
    for name, source in MODELS.items():
        with urllib.request.urlopen(f'https://raw.githubusercontent.com/opencv/opencv_zoo/{REVISION}/{source}', timeout=60) as response:
            pointer = response.read().decode()
        expected = next(line.split('sha256:')[1] for line in pointer.splitlines() if line.startswith('oid sha256:'))
        target = destination / name
        if target.exists() and hashlib.sha256(target.read_bytes()).hexdigest() == expected:
            print(f'{name}: verified')
            continue
        temporary = target.with_suffix('.download')
        urllib.request.urlretrieve(f'https://media.githubusercontent.com/media/opencv/opencv_zoo/{REVISION}/{source}', temporary)
        if hashlib.sha256(temporary.read_bytes()).hexdigest() != expected:
            temporary.unlink()
            raise RuntimeError(f'Checksum mismatch: {name}')
        temporary.replace(target)
        print(f'{name}: downloaded and verified')
    for name, folder in [('LICENSE-YUNET', 'face_detection_yunet'), ('LICENSE-SFACE', 'face_recognition_sface')]:
        urllib.request.urlretrieve(f'https://raw.githubusercontent.com/opencv/opencv_zoo/{REVISION}/models/{folder}/LICENSE', destination / name)


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--destination', type=Path, default=Path(__file__).resolve().parents[2] / 'storage/app/private/face-models')
    setup(parser.parse_args().destination)
