<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var list<string> $headers
 * @var list<list<mixed>> $rows
 */

$lastCellIdx = count($rows[0]) - 1;
?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
            <tr>
                <?php foreach ($headers as $header): ?>
                    <th><?= Html::encode($header) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= count($headers) ?>" class="text-muted">No records.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $i => $cell): ?>
                            <td <?php if ($i === $lastCellIdx): ?>class="text-end"<?php endif; ?>>
                                <?php if (is_array($cell) && isset($cell['html'])): ?>
                                    <?= (string) $cell['html'] ?>
                                <?php else: ?>
                                    <?= Html::encode((string) $cell) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

