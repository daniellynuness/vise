<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acessos - vise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
  </head>
  <body>
    <div class="container-fluid">
      <header class="d-flex flex-wrap justify-content-center py-3 mb-4 border-bottom">
        <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
          <span class="fs-4">vise</span>
        </a>
        <ul class="nav nav-pills">
          <li class="nav-item"><a href="index.php" class="nav-link">Links</a></li>
          <li class="nav-item"><a href="accesses.php" class="nav-link active" aria-current="page">Acessos</a></li>
          <li class="nav-item"><span class="nav-link disabled">_USER_EMAIL_</span></li>
          <li class="nav-item"><a href="logout.php" class="nav-link">Sair</a></li>
        </ul>
      </header>

      <h1 class="h4">Últimos acessos</h1>
      <p class="text-muted">Exibindo no máximo os 100 acessos mais recentes.</p>

      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Data/hora</th>
              <th>Handle</th>
              <th>Destino</th>
              <th>IP</th>
              <th>Navegador</th>
              <th>Origem</th>
            </tr>
          </thead>
          <tbody>
            _ACCESS_ROWS_
          </tbody>
        </table>
      </div>
    </div>
  </body>
</html>
