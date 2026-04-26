<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Peringatan Stok Kadaluwarsa</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px solid #e74c3c; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #2c3e50; font-size: 24px; margin: 0; }
        .location { font-weight: bold; color: #e74c3c; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; color: #2c3e50; }
        .section-title { font-weight: bold; margin-top: 25px; padding: 10px; border-radius: 4px; color: white; display: inline-block; width: 100%; }
        .bg-expired { background-color: #34495e; }
        .bg-critical { background-color: #e74c3c; }
        .bg-warning { background-color: #f39c12; }
        .bg-slow-moving { background-color: #f1c40f; color: #333; }
        .footer { text-align: center; font-size: 12px; color: #7f8c8d; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #e74c3c; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Peringatan Stok Kadaluwarsa</h1>
            <p>Lokasi: <span class="location">{{ $locationName }}</span></p>
        </div>

        <p>Halo Team,</p>
        <p>Mohon periksa daftar stok berikut yang memerlukan perhatian segera terkait masa kadaluwarsanya:</p>

        @php
            $categories = [
                'expired'  => ['label' => '🛑 EXPIRED (SUDAH LEWAT)', 'bg' => 'bg-expired'],
                'critical' => ['label' => '🔴 KRITIS (≤ 7 HARI)', 'bg' => 'bg-critical'],
                'warning'  => ['label' => '🟠 WARNING (≤ 30 HARI)', 'bg' => 'bg-warning'],
                'slow_moving' => ['label' => '🟡 PROMO/LAMBAT (≤ 90 HARI)', 'bg' => 'bg-slow-moving'],
            ];
        @endphp

        @foreach($categories as $key => $cat)
            @php
                $items = array_filter($expiryDetails, fn($d) => $d['status'] === $key);
            @endphp

            @if(!empty($items))
                <div class="section-title {{ $cat['bg'] }}">{{ $cat['label'] }}</div>
                <table>
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>No Batch</th>
                            <th>Tgl Kadaluwarsa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td><strong>{{ $item['name'] }}</strong></td>
                            <td>{{ $item['batch_number'] }}</td>
                            <td>{{ date('d M Y', strtotime($item['expiry_date'])) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach

        <p>Mohon segera lakukan pemindahan, retur, atau tindakan lain yang diperlukan untuk meminimalkan kerugian stok.</p>

        <center>
            <a href="{{ config('app.url') }}" class="btn">Buka Dashboard Inventaris</a>
        </center>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} - RS Gigi</p>
            <p>Email ini dikirim secara otomatis oleh sistem.</p>
        </div>
    </div>
</body>
</html>
