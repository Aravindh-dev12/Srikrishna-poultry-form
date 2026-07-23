<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title>Vijayanath Solar Plants – Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .spinner { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-slate-900 text-slate-800 antialiased min-h-screen flex flex-col">
    <div id="login-view" class="min-h-screen flex items-center justify-center bg-cover bg-center bg-no-repeat px-4"
        style="background-image: linear-gradient(rgba(15,23,42,.55), rgba(15,23,42,.65)), url('solar.jpeg');">
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 p-8 sm:p-10 rounded-2xl shadow-2xl w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-14 h-14 bg-white/20 text-white border border-white/30 rounded-xl mx-auto flex items-center justify-center text-2xl shadow-lg mb-4">
                    <i class="fa-solid fa-solar-panel"></i>
                </div>
                <h2 class="text-3xl font-bold text-white">Welcome Back</h2>
                <p class="text-sm text-slate-300 mt-2">Sign in to manage your solar plants</p>
            </div>

            <form id="loginForm" class="space-y-5" autocomplete="on">
                <div>
                    <label class="block text-xs font-semibold text-slate-200 mb-1">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder="you@example.com"
                        class="w-full px-4 py-3 text-sm bg-white/10 border border-white/20 text-white placeholder-slate-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400/50 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-200 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                            class="w-full px-4 py-3 text-sm bg-white/10 border border-white/20 text-white placeholder-slate-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400/50 transition-colors pr-11">
                        <button type="button" id="togglePwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                            <i class="fa-solid fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" id="loginBtn" class="w-full py-3 px-4 text-sm text-white font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-500 active:scale-[.98] transition-all mt-2 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In
                </button>
                <p id="login-error" class="text-red-300 text-xs text-center hidden font-medium mt-2"></p>
            </form>

            <p class="text-center text-[10px] text-slate-400 mt-6 tracking-wide uppercase font-semibold">
                Oriks Care Private limited &bull; Solar SCADA Platform
            </p>
        </div>
    </div>

    <script>
    (function() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('logout') || params.get('expired')) {
            sessionStorage.clear();
            localStorage.removeItem('vs_token');
            localStorage.removeItem('vs_user');
            localStorage.removeItem('userRole');
            return;
        }

        const token = sessionStorage.getItem('vs_token');
        if (!token) return;
        if (sessionStorage.getItem('vs_redirect_guard')) {
            sessionStorage.removeItem('vs_token');
            sessionStorage.removeItem('vs_user');
            sessionStorage.removeItem('vs_redirect_guard');
            return;
        }

        sessionStorage.setItem('vs_redirect_guard', '1');
        fetch('api.php?action=get_user', { headers: { Authorization: 'Bearer ' + token } })
            .then(r => r.json())
            .then(d => {
                sessionStorage.removeItem('vs_redirect_guard');
                if (d.status === 'success' && d.user) {
                    if (d.user.role === 'admin') window.location.replace('admin.php');
                    else if (d.user.plant_id) window.location.replace('overview.php?plant=' + encodeURIComponent(d.user.plant_id));
                    else {
                        sessionStorage.removeItem('vs_token');
                        sessionStorage.removeItem('vs_user');
                    }
                } else {
                    sessionStorage.removeItem('vs_token');
                    sessionStorage.removeItem('vs_user');
                }
            })
            .catch(() => sessionStorage.removeItem('vs_redirect_guard'));
    })();

    document.getElementById('togglePwd').addEventListener('click', function() {
        const pwd = document.getElementById('password');
        const icon = this.querySelector('i');
        pwd.type = pwd.type === 'password' ? 'text' : 'password';
        icon.className = pwd.type === 'password' ? 'fa-solid fa-eye text-sm' : 'fa-solid fa-eye-slash text-sm';
    });

    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('loginBtn');
        const errEl = document.getElementById('login-error');
        const email = document.getElementById('email').value.trim().toLowerCase();
        errEl.classList.add('hidden');
        btn.innerHTML = '<i class="fa-solid fa-circle-notch spinner"></i> Verifying...';
        btn.disabled = true;

        try {
            const res = await fetch('api_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password: document.getElementById('password').value })
            });
            const data = await res.json();

            if (data.status === 'success') {
                sessionStorage.setItem('vs_token', data.token);
                sessionStorage.setItem('vs_user', JSON.stringify(data.user));
                sessionStorage.setItem('vs_current_plant', data.user.plant_id || 'vijayanth');
                localStorage.removeItem('vs_token');
                localStorage.removeItem('vs_user');
                localStorage.removeItem('userRole');

                if (data.user.role === 'admin') window.location.replace('admin.php');
                else if (data.user.plant_id) window.location.replace('overview.php?plant=' + encodeURIComponent(data.user.plant_id));
                else {
                    errEl.textContent = 'No plant assigned to this user.';
                    errEl.classList.remove('hidden');
                }
            } else {
                errEl.textContent = data.message || 'Invalid credentials.';
                errEl.classList.remove('hidden');
            }
        } catch (err) {
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.remove('hidden');
            console.error('Login error:', err);
        } finally {
            btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Sign In';
            btn.disabled = false;
        }
    });
    </script>
</body>
</html>
