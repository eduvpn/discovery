<html>
<head>
<title>Organization List</title>
</head>
<body>
<p>
We currently have <?=\count($orgList); ?> organizations listed.
</p>
<?php foreach ($orgList as $orgInfo): ?>
    <details>
        <summary><?=\array_shift($orgInfo['display_name']); ?></summary>
        <pre><?=\json_encode($orgInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
    </details>
<?php endforeach; ?>
</body>
</html>
