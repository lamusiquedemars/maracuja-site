<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Déconnexion…</title>
</head>
<body>
    <form id="single-logout" method="post" action="{{ $endpoint }}">
        <input type="hidden" name="client_id" value="{{ $clientId }}">
        <input type="hidden" name="state" value="{{ $state }}">
        <p>Déconnexion en cours…</p>
        <button type="submit">Terminer la déconnexion</button>
    </form>
    <script>document.getElementById('single-logout').submit();</script>
</body>
</html>
