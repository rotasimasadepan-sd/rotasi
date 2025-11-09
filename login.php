<?php
require_once 'db.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM guru WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $username, $hashed_password);
            if ($stmt->fetch()) {
                if (password_verify($password, $hashed_password)) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id_guru"] = $id;
                    $_SESSION["username"] = $username;
                    
                    header("location: teacher/dashboard.php");
                    exit;
                } else {
                    $error = "Password yang Anda masukkan salah.";
                }
            }
        } else {
            $error = "Akun dengan username tersebut tidak ditemukan.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Guru - Aplikasi Ujian Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-slate-900">Selamat Datang, Guru!</h1>
                <p class="text-slate-500 mt-2">Masuk untuk mulai mengelola ujian online.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl shadow-lg">
                <h2 class="text-2xl font-semibold text-center mb-6">Login Akun</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-slate-600 mb-2">Username</label>
                        <input type="text" name="username" id="username" required class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-600 mb-2">Password</label>
                        <input type="password" name="password" id="password" required class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform transform hover:scale-105">
                            Login
                        </button>
                    </div>
                </form>
                <p class="text-center text-sm text-slate-500 mt-8">
                    Belum punya akun? <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">Daftar di sini</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
