<?php
http_response_code(500);
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Internal Server Error</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 2rem; color: #222; }
    .card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem 1.25rem; max-width: 800px; }
    h1 { margin-top: 0; font-size: 1.5rem; }
    code { background: #f6f8fa; padding: 0.2rem 0.35rem; border-radius: 4px; }
    .hint { color: #666; font-size: 0.95rem; }
  </style>
</head>
<body>
  <div class="card">
    <h1>500 Internal Server Error</h1>
    <p>Something went wrong while processing your request.</p>
    <p class="hint">Please try again or contact an administrator.</p>
    <hr>
    <p class="hint">Admins: Check <code>c:\xampp\htdocs\bts\logs\error.log</code> for details.</p>
  </div>
</body>
</html>
