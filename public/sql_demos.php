<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';

$rawSql = $_POST['sql'] ?? '';
$rawParams = $_POST['params'] ?? '';
$result = null;
$error = null;
$info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  [$ok, $msg] = validate_demo_sql($rawSql);
  if (!$ok) {
    $error = $msg;
  } else {
    try {
      $params = parse_params($rawParams);
      $stmt = $pdo->prepare($rawSql);
      foreach ($params as $name => $data) {
        // Allow both :name and name
        $paramName = str_starts_with($name, ':') ? $name : (":" . $name);
        $stmt->bindValue($paramName, $data['value'], $data['type']);
      }
      $stmt->execute();
      // Fetch results if SELECT or EXPLAIN
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $info = [
        'rowCount' => $stmt->rowCount(),
        'columns' => $stmt->columnCount(),
      ];
    } catch (Throwable $t) {
      $error = $t->getMessage();
    }
  }
}

$pageTitle = 'SQL Demo';
require __DIR__ . '/header.php';
?>
  <h2>SQL Demo Runner</h2>
  <p class="muted">Only SELECT and EXPLAIN statements are allowed. Multiple statements are blocked. Use named placeholders (e.g., <code>:id</code>), and provide parameters below.</p>

  <form method="post" action="">
    <label for="sql"><strong>SQL</strong></label><br />
    <textarea id="sql" name="sql" placeholder="SELECT * FROM vehicles WHERE id = :id;"><?= e($rawSql) ?></textarea>

    <div style="margin-top: .5rem"></div>
    <label for="params"><strong>Params</strong> <span class="muted">(one per line: name=value; use :int:, :float:, :bool: prefixes)</span></label><br />
    <textarea id="params" name="params" placeholder=":id=:int:1&#10;:active=:bool:true"><?= e($rawParams) ?></textarea>

    <div style="margin-top: .5rem"></div>
    <button type="submit">Run</button>
    <a href="index.php" style="margin-left: 1rem">Back</a>
  </form>

  <?php if ($error): ?>
    <p class="err"><strong>Error:</strong> <?= e($error) ?></p>
  <?php endif; ?>

  <?php if ($result !== null && !$error): ?>
    <h3>Result</h3>
    <?php if (empty($result)): ?>
      <p class="muted">No rows.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <?php foreach (array_keys($result[0]) as $col): ?>
              <th><?= e((string)$col) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($result as $row): ?>
            <tr>
              <?php foreach ($row as $val): ?>
                <td><?= e(is_scalar($val) ? (string)$val : json_encode($val)) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="muted">Rows: <?= e((string)$info['rowCount']) ?>, Columns: <?= e((string)$info['columns']) ?></p>
    <?php endif; ?>
  <?php endif; ?>

  <h3>Tips</h3>
  <ul>
    <li>Named placeholders: <code>:name</code>. Example: <code>SELECT * FROM vehicles WHERE id = :id</code></li>
    <li>Params format examples:
      <pre>:id=:int:1
:active=:bool:true
:rate=:float:3.14
:search=%truck%</pre>
    </li>
  </ul>
<?php require __DIR__ . '/footer.php'; ?>
