<!DOCTYPE html>
<html>
<head>
    <title>Debug Upload</title>
</head>
<body>
    <h1>Debug Upload Test</h1>
    
    @if(session('success'))
        <div style="color: green; font-weight: bold;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="/dataset/upload" method="POST" enctype="multipart/form-data">
        @csrf
        <label>Select CSV File:</label>
        <br>
        <input type="file" name="file" required>
        <br><br>
        <button type="submit">UPLOAD RAW FILE</button>
    </form>
</body>
</html>
