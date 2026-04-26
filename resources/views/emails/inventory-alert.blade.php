<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Peringatan Stok Rendah</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #2c3e50; font-size: 24px; margin: 0; }
        .location { font-weight: bold; color: #3498db; }
        .alert-item { margin-bottom: 15px; padding: 15px; border-radius: 6px; }
        .status-critical { background-color: #fce4e4; border-left: 5px solid #e74c3c; }
        .status-warning { background-color: #fff3e0; border-left: 5px solid #f39c12; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; color: #2c3e50; }
        .label { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .label-critical { background-color: #e74c3c; color: white; }
        .label-warning { background-color: #f39c12; color: white; }
        .footer { text-align: center; font-size: 12px; color: #7f8c8d; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Peringatan Stok Rendah</h1>
            <p>Lokasi: <span class="location">{{ $locationName }}</span></p>
        </div>

        <p>Halo Team,</p>
        <p>Mohon periksa daftar barang berikut yang stoknya mulai menipis atau dalam kondisi kritis:</p>

        <table>
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Status</th>
                    <th>Stok Tersisa</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alerts as $alert)
                <tr>
                    <td><strong>{{ $alert['name'] }}</strong></td>
                    <td>
                        <span class="label label-{{ $alert['status'] }}">
                            {{ $alert['status'] }}
                        </span>
                    </td>
                    <td>{{ $alert['stock'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p>Mohon segera lakukan pengadaan atau restock barang di atas untuk memastikan operasional berjalan lancar.</p>

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
