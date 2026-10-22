<h1>hello {{ $username }}</h1>
<h2>This is your code : {{ $code }}</h2>
<p>
    <a href="{{ url('/api/resend-code?email=' . urlencode($email)) }}">
        Resend verification code
    </a>
</p>
