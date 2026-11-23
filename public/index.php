<?php
// Auto-detect Docker Frontend Port from root .env
$envFile = dirname(__DIR__) . '/.env';
$fePort = 5174;

if (file_exists($envFile)) {
    $content = file_get_contents($envFile);
    if (preg_match('/DOCKER_FE_PORT=(\d+)/', $content, $matches)) {
        $fePort = $matches[1];
    }
}

// Redirect dynamically to the active Docker frontend port
header("Location: http://localhost:" . $fePort);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="0; url=http://localhost:<?php echo $fePort; ?>">
  <title>Mengarahkan ke BWS-LIS...</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: #f8fafc;
      color: #002855;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 80vh;
      text-align: center;
      margin: 0;
      padding: 20px;
    }
    .box {
      background: #ffffff;
      padding: 40px;
      border-radius: 24px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
      border: 1px solid #e2e8f0;
      max-width: 480px;
    }
    a.btn {
      display: inline-block;
      margin-top: 15px;
      padding: 12px 24px;
      background: #0067AC;
      color: #ffffff;
      text-decoration: none;
      font-weight: bold;
      border-radius: 12px;
      transition: background 0.2s;
    }
    a.btn:hover {
      background: #002855;
    }
    .hint {
      margin-top: 20px;
      font-size: 12px;
      color: #64748b;
    }
  </style>
</head>
<body>
  <div class="box">
    <h2>Mengarahkan ke Portal BWS-LIS...</h2>
    <p>Aplikasi Frontend berjalan di <strong>http://localhost:<?php echo $fePort; ?></strong></p>
    <a href="http://localhost:<?php echo $fePort; ?>" class="btn">Buka Portal BWS-LIS</a>
    <p class="hint">
      Tip: Anda juga dapat menjalankan <code>start.bat</code> di folder project.
    </p>
  </div>
</body>
</html>
