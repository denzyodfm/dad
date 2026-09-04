<?php
declare(strict_types=1);

/**
 * Creates editable starter content for the Writing section.
 *
 * Safe to run more than once: an existing entry with the same original slug
 * is left untouched, so edits made later in the studio are never overwritten.
 */

namespace App;

require_once __DIR__ . '/../app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$samples = [
    'article' => [
        [
            'title' => 'Designing Approval Workflows That People Actually Use',
            'summary' => 'A practical approach to turning informal requests into clear, traceable decisions without slowing the team down.',
            'category' => 'Sample article · Workflow design',
            'published_at' => '2026-08-28',
            'body' => '<p>A good approval workflow does more than move a request from one person to another. It makes ownership visible, gives reviewers the context they need, and tells the requester exactly what happens next.</p><h3>Start with the decision</h3><p>Before building screens, define the decision being made, who is allowed to make it, and what evidence they need. This keeps forms focused and prevents unnecessary fields from becoming permanent process baggage.</p><h3>Make status meaningful</h3><p>Use a small set of states that describe real business moments: draft, submitted, under review, approved, rejected, and completed. Every transition should have an owner and a timestamp so the history remains understandable months later.</p><p>The best workflow feels lighter than the email-and-spreadsheet process it replaces. Automation should remove follow-up work while keeping exceptions easy to see and resolve.</p>',
        ],
        [
            'title' => 'From Spreadsheet to Multi-User Business System',
            'summary' => 'What to preserve, redesign, and automate when a spreadsheet has outgrown the team using it.',
            'category' => 'Sample article · Business systems',
            'published_at' => '2026-08-20',
            'body' => '<p>Spreadsheets are often the first useful version of a business system. They are flexible, familiar, and fast to change. Problems begin when several people need to edit the same records, definitions drift, or management needs a reliable history.</p><h3>Preserve the working knowledge</h3><p>The spreadsheet contains more than data. Its columns, formulas, colors, and workarounds reveal how the team actually operates. Migration should begin by understanding that knowledge instead of copying every sheet directly into a database.</p><h3>Separate records from reports</h3><p>A multi-user system should store each business event once and generate views from those records. Permissions, validation, and audit history then become consistent rather than depending on who last edited a file.</p><p>A staged rollout works best: stabilize the core records first, verify totals with users, and automate notifications and reporting only after the underlying workflow is trusted.</p>',
        ],
        [
            'title' => 'Practical Security for Small Internal Web Applications',
            'summary' => 'A compact security baseline for applications that handle operational data, accounts, and everyday business workflows.',
            'category' => 'Sample article · Application security',
            'published_at' => '2026-08-12',
            'body' => '<p>Internal does not mean harmless. A small operational application may hold employee details, financial records, customer information, or credentials that deserve the same disciplined protection as a public product.</p><h3>Build the baseline first</h3><p>Use strong password hashing, server-side sessions, rate limiting, CSRF protection, prepared SQL statements, and least-privilege database accounts. HTTPS should be the default even when traffic crosses a private network.</p><h3>Limit what failure can reach</h3><p>Uploads need strict type validation and non-executable storage. Administrative routes should require explicit roles. Secrets belong outside the public web root, while backups should be encrypted and tested through actual restoration.</p><p>Security is most sustainable when these controls are part of the application template. Teams can then focus each review on the risks unique to the workflow rather than rebuilding the foundation every time.</p>',
        ],
    ],
    'note' => [
        [
            'title' => 'Why Workflow Status and Payment Status Should Stay Separate',
            'summary' => 'One record can be approved but unpaid, or paid while another operational step remains open.',
            'category' => 'Sample note · Data modeling',
            'published_at' => '2026-08-05',
            'body' => '<p>A single status field often tries to describe too much. Approval, fulfillment, payment, and reconciliation are different processes with different owners and timelines.</p><p>Keeping their statuses separate prevents ambiguous values and makes reports more accurate. A request can be approved, partially fulfilled, unpaid, and awaiting reconciliation without forcing those facts into one label.</p>',
        ],
        [
            'title' => 'A Small Rule for Better Operational Dashboards',
            'summary' => 'Every number should help someone notice a condition or decide what to do next.',
            'category' => 'Sample note · Interface design',
            'published_at' => '2026-07-29',
            'body' => '<p>Before adding a metric, ask which decision changes when the number moves. If there is no clear answer, it may belong in a report rather than on the dashboard.</p><p>Useful dashboards emphasize exceptions, aging items, workload, and trends. The goal is not to display everything the database knows; it is to make the next action easier to recognize.</p>',
        ],
        [
            'title' => 'Backups Are Only Real After a Restore Test',
            'summary' => 'A successful backup job proves that files were written, not that the service can be recovered.',
            'category' => 'Sample note · Infrastructure',
            'published_at' => '2026-07-22',
            'body' => '<p>A restore test verifies the entire recovery path: credentials, encryption keys, database integrity, application files, dependencies, and the instructions another administrator may need during an incident.</p><p>Schedule small restoration drills and record the recovery time. The result turns a backup assumption into evidence and often reveals simple fixes long before an emergency.</p>',
        ],
    ],
];

$content = new Content($pdo);
$authorId = $pdo->query(
    "SELECT id FROM users WHERE role = 'admin' AND status = 'active' ORDER BY id ASC LIMIT 1"
)->fetchColumn();
$created = 0;
$skipped = 0;

foreach ($samples as $typeSlug => $entries) {
    $type = $content->typeBySlug($typeSlug);
    if ($type === null) {
        fwrite(STDERR, "Missing content type: {$typeSlug}. Run seed_content.php first.\n");
        exit(1);
    }

    foreach ($entries as $order => $sample) {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $sample['title']), '-'));
        if ($content->entryBySlug($slug, false) !== null) {
            echo "Kept existing entry: {$sample['title']}\n";
            $skipped++;
            continue;
        }

        $content->createEntry($sample + [
            'type_id' => (int) $type['id'],
            'author_id' => $authorId === false ? null : (int) $authorId,
            'status' => 'published',
            'sort_order' => $order,
            'accent' => 'outline',
        ]);
        echo "Created {$type['name']}: {$sample['title']}\n";
        $created++;
    }
}

echo "\n{$created} sample entries created; {$skipped} existing entries left unchanged.\n";
