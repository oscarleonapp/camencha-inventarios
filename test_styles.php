<?php
$titulo = 'Style Audit';
include 'includes/layout_header.php';
?>

<div class="container-fluid">
  <div class="row g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-palette me-2"></i>Tokens de Color</h5>
        </div>
        <div class="card-body">
          <div class="row g-3 text-center">
            <?php
              $tokens = [
                ['--primary-color','Primary'],
                ['--secondary-color','Secondary'],
                ['--success-color','Success'],
                ['--danger-color','Danger'],
                ['--warning-color','Warning'],
                ['--info-color','Info'],
                ['--text-primary','Text Primary'],
                ['--text-secondary','Text Secondary'],
                ['--text-muted','Text Muted'],
                ['--surface','Surface'],
                ['--surface-subtle','Surface Subtle'],
                ['--border-color','Border']
              ];
              foreach ($tokens as [$var,$name]): ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                  <div class="p-3 border rounded" style="background: var(<?= $var ?>); color: #111;">
                    <div class="small"><?= $name ?></div>
                    <code class="small"><?= $var ?></code>
                  </div>
                </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Buttons</h5></div>
        <div class="card-body d-flex flex-wrap gap-2">
          <button class="btn btn-primary">Primary</button>
          <button class="btn btn-secondary">Secondary</button>
          <button class="btn btn-success">Success</button>
          <button class="btn btn-danger">Danger</button>
          <button class="btn btn-warning">Warning</button>
          <button class="btn btn-info">Info</button>
          <button class="btn btn-outline-primary">Outline Primary</button>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Badges & Text</h5></div>
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
          <span class="badge badge-primary">Primary</span>
          <span class="badge badge-success">Success</span>
          <span class="badge badge-danger">Danger</span>
          <span class="badge badge-warning">Warning</span>
          <span class="badge badge-info">Info</span>
          <span class="text-primary">text-primary</span>
          <span class="text-secondary">text-secondary</span>
          <span class="text-success">text-success</span>
          <span class="text-danger">text-danger</span>
          <span class="text-warning">text-warning</span>
          <span class="text-info">text-info</span>
          <span class="text-muted">text-muted</span>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Alerts</h5></div>
        <div class="card-body">
          <div class="alert alert-success" role="alert">Alert success</div>
          <div class="alert alert-danger" role="alert">Alert danger</div>
          <div class="alert alert-warning" role="alert">Alert warning</div>
          <div class="alert alert-info" role="alert">Alert info</div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Backgrounds</h5></div>
        <div class="card-body d-grid gap-2">
          <div class="p-3 bg-primary">bg-primary</div>
          <div class="p-3 bg-success">bg-success</div>
          <div class="p-3 bg-danger">bg-danger</div>
          <div class="p-3 bg-warning">bg-warning (texto debe ser oscuro)</div>
          <div class="p-3 bg-info">bg-info</div>
          <div class="p-3 bg-light">bg-light</div>
          <div class="p-3 bg-white border">bg-white</div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Tables</h5></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table accessibility-fix">
              <thead>
                <tr><th>Header</th><th>Header</th><th>Header</th></tr>
              </thead>
              <tbody>
                <tr><td>A</td><td>B</td><td>C</td></tr>
                <tr><td>A</td><td>B</td><td>C</td></tr>
              </tbody>
            </table>
          </div>
          <div class="table-responsive">
            <table class="table accessibility-fix">
              <thead class="thead-titulos">
                <tr><th>Header</th><th>Header</th><th>Header</th></tr>
              </thead>
              <tbody>
                <tr><td>A</td><td>B</td><td>C</td></tr>
                <tr><td>A</td><td>B</td><td>C</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Forms & Focus</h5></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Texto</label>
              <input class="form-control" placeholder="Escribe para ver foco">
            </div>
            <div class="col-md-6">
              <label class="form-label">Select</label>
              <select class="form-select">
                <option>Opción 1</option>
                <option>Opción 2</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Toasts</h5></div>
        <div class="card-body d-flex flex-wrap gap-2">
          <button class="btn btn-success" onclick="showToast('Operación exitosa','success')">Success</button>
          <button class="btn btn-danger" onclick="showToast('Hubo un error','danger')">Danger</button>
          <button class="btn btn-warning" onclick="showToast('Revisa los campos','warning')">Warning</button>
          <button class="btn btn-info" onclick="showToast('Información útil','info')">Info</button>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/layout_footer.php'; ?>
