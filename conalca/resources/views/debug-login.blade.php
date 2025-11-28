<!DOCTYPE html>
<html>
<head>
    <title>Debug Login</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; }
        button { padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer; }
        #result { margin-top: 20px; padding: 15px; background: #f0f0f0; border: 1px solid #ccc; display: none; white-space: pre-wrap; }
        .success { border-color: green; background: #e8f5e9; }
        .error { border-color: red; background: #ffebee; }
    </style>
</head>
<body>
    <h1>🕵️ Debug Login</h1>
    <p>Esta herramienta probará el inicio de sesión y mostrará el resultado técnico.</p>
    
    <form id="loginForm">
        @csrf
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="DonDavidNavarro@conalca.com" required>
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="text" name="password" value="123456789" required>
        </div>
        <button type="submit">Probar Login</button>
    </form>

    <div id="result"></div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'block';
            resultDiv.textContent = 'Procesando...';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('/debug-login-simulation', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(Object.fromEntries(formData))
                });
                
                const data = await response.json();
                resultDiv.textContent = JSON.stringify(data, null, 2);
                
                if (data.auth_attempt && data.auth_attempt.success) {
                    resultDiv.className = 'success';
                } else {
                    resultDiv.className = 'error';
                }
            } catch (error) {
                resultDiv.textContent = 'Error de red: ' + error.message;
                resultDiv.className = 'error';
            }
        });
    </script>
</body>
</html>
