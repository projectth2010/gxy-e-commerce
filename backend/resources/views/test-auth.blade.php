<!DOCTYPE html>
<html>
<head>
    <title>Test Authentication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Test Authentication</h3>
                    </div>
                    
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        
                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if($token)
                            <div class="alert alert-success">
                                <h5>Authentication Successful!</h5>
                                <p><strong>Token:</strong> {{ substr($token, 0, 30) }}...</p>
                                <p><strong>User:</strong> {{ $user['name'] }} ({{ $user['email'] }})</p>
                                <p><strong>Is Admin:</strong> {{ $user['is_admin'] ? 'Yes' : 'No' }}</p>
                                
                                <h5 class="mt-4">Test API Endpoints</h5>
                                <div class="mb-3">
                                    <button class="btn btn-primary me-2" onclick="testEndpoint('{{ $apiUrl }}/user')">Test /user</button>
                                    <button class="btn btn-info me-2" onclick="testEndpoint('{{ $apiUrl }}/center/plans')">Test /plans</button>
                                    <button class="btn btn-info me-2" onclick="testEndpoint('{{ $apiUrl }}/center/features')">Test /features</button>
                                </div>
                                
                                <div id="apiResponse" class="mt-3 p-3 bg-light rounded" style="min-height: 100px;">
                                    <p class="text-muted">API response will appear here...</p>
                                </div>
                                
                                <form action="{{ url('/test-auth/logout') }}" method="POST" class="mt-4">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">Logout</button>
                                </form>
                            </div>
                        @else
                            <form action="{{ url('/test-auth/login') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="admin@example.com" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" value="admin123" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Login</button>
                            </form>
                        @endif
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Test API with cURL</h5>
                    </div>
                    <div class="card-body">
                        <h6>Login:</h6>
                        <pre class="bg-light p-2 rounded">
curl -X POST {{ url('/api/login') }} \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "admin123", "device_name": "test-device"}'</pre>
                        
                        @if($token)
                            <h6 class="mt-3">Get User Info:</h6>
                            <pre class="bg-light p-2 rounded">
curl -X GET {{ url('/api/user') }} \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {{ $token }}"</pre>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function testEndpoint(url) {
            const responseDiv = document.getElementById('apiResponse');
            responseDiv.innerHTML = '<p>Loading...</p>';
            
            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer {{ $token }}'
                    }
                });
                
                const data = await response.json();
                responseDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                responseDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            }
        }
    </script>
</body>
</html>
