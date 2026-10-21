<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar link - vise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
  </head>
  <body>
    <div class="container">
      <header class="d-flex flex-wrap justify-content-center py-3 mb-4 border-bottom">
        <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
          <span class="fs-4">vise</span>
        </a>
        <ul class="nav nav-pills">
          <li class="nav-item"><span class="nav-link disabled">_USER_EMAIL_</span></li>
          <li class="nav-item"><a href="logout.php" class="nav-link">Sair</a></li>
        </ul>
      </header>

      _MESSAGE_

      <h1 class="h4">Editar link</h1>
      <form class="row g-3" method="post" action="edit.php?handle=_HANDLE_">
        <input type="hidden" name="handle" value="_HANDLE_">
        <div class="col-md-6">
          <label for="handle" class="form-label">Handle</label>
          <div class="input-group">
            <span class="input-group-text">menteb.in/</span>
            <input type="text" class="form-control" id="handle" value="_HANDLE_" disabled>
          </div>
        </div>
        <div class="col-md-10">
          <label for="dest" class="form-label">URL de destino</label>
          <input type="url" class="form-control" id="dest" name="dest" value="_DESTINATION_" required>
        </div>
        <div class="col-12">
          <button class="btn btn-primary" type="submit">Salvar alterações</button>
          <a class="btn btn-secondary" href="index.php">Cancelar</a>
        </div>
      </form>
    </div>
  </body>
</html>
