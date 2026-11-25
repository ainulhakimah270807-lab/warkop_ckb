<?php
namespace App\Http\Controllers;
use App\Models\Sale;
use Illuminate\Http\Request;
class ReportController extends Controller
{
    private function data(Request $request): array
    {
        $period = $request->query('period', '7_hari');
        abort_unless(in_array($period, ['hari_ini', '7_hari', 'bulan_ini']), 422, 'Periode tidak valid.');
        $end = now('Asia/Jakarta');
        $start = match ($period) { 'hari_ini' => $end->copy()->startOfDay(), 'bulan_ini' => $end->copy()->startOfMonth(), default => $end->copy()->subDays(6)->startOfDay() };
        $query = Sale::whereBetween('business_date', [$start->toDateString(), $end->toDateString()]);
        $reports = (clone $query)->selectRaw('business_date as date, COUNT(*) as transactions, SUM(total) as revenue, AVG(total) as average')->groupBy('business_date')->orderByDesc('business_date')->get();
        $count = (clone $query)->count(); $revenue = (float) (clone $query)->sum('total');
        return compact('period', 'reports', 'count', 'revenue');
    }
    public function index(Request $request) { return view('reports.index', $this->data($request)); }
    public function export(Request $request)
    {
        $data = $this->data($request);
        return response()->streamDownload(function () use ($data) {
            $file = fopen('php://output', 'w'); fputcsv($file, ['Tanggal WIB', 'Transaksi', 'Omzet termasuk pajak', 'Rata-rata']);
            foreach ($data['reports'] as $row) fputcsv($file, [$row->date, $row->transactions, $row->revenue, round($row->average)]);
            fclose($file);
        }, 'laporan-penjualan.csv', ['Content-Type' => 'text/csv']);
    }
}
