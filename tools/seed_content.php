<?php
declare(strict_types=1);

/**
 * Seeds the content tables with the site's existing entries.
 *
 * The four project cards and their detail panels were hardcoded in
 * index.html; this puts the same words in the database so the rendered page
 * is unchanged. Safe to re-run: existing slugs are skipped.
 *
 *   php tools/seed_content.php
 *   php tools/seed_content.php --force   # rewrite the seeded entries
 */

namespace App;

require_once __DIR__ . '/../app/src/Env.php';

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = __DIR__ . '/../app/src/' . substr($class, 4) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use Throwable;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$force = in_array('--force', array_slice($argv, 1), true);

$envFile = null;
foreach ([dirname($root) . '/.env', $root . '/.env'] as $candidate) {
    if (is_readable($candidate)) {
        $envFile = $candidate;
        break;
    }
}
if ($envFile === null) {
    fwrite(STDERR, "No .env found. Run php tools/serve.php once first.\n");
    exit(1);
}
Env::load($envFile);
date_default_timezone_set('UTC');

$types = [
    ['name' => 'Project', 'placement' => 'portfolio', 'sort_order' => 0],
    ['name' => 'Article', 'placement' => 'writing', 'sort_order' => 1],
    ['name' => 'Note', 'placement' => 'writing', 'sort_order' => 2],
];

$projects = [
    [
        'title' => 'Centralized Lending',
        'card_heading' => 'Centralized|Lending',
        'kicker' => 'Multi-branch operations',
        'meta' => 'FileMaker · API',
        'accent' => 'cobalt',
        'category' => 'Lending',
        'summary' => 'One platform across every branch.',
        'body' => '<p>A unified FileMaker platform for client verification, loan origination, '
            . 'approval, credit scoring, payment monitoring, and remedial action. REST services '
            . 'connect database events with automated SMS notifications.</p>',
        'link_url' => 'https://github.com/denzyodfm/alc-client-inquiry-system',
        'link_label' => 'View related repository',
        'facts' => [
            ['Outcome', '30% fewer processing delays'],
            ['Scope', 'Multi-branch synchronization'],
            ['Stack', 'FileMaker Server · Data API · REST'],
            ['Security', 'Roles · Encryption · Off-site backups'],
        ],
    ],
    [
        'title' => 'HR Information System',
        'card_heading' => 'HR Information|System',
        'kicker' => 'People operations',
        'meta' => '150+ employees',
        'accent' => 'ink',
        'category' => 'Human resources',
        'summary' => 'People operations in one workflow.',
        'body' => '<p>A FileMaker solution for leave filing, medical reimbursement, performance '
            . 'evaluations, and payroll processing—replacing disconnected spreadsheets with '
            . 'traceable processes.</p>',
        'link_url' => null,
        'link_label' => null,
        'facts' => [
            ['Reach', '150+ employees'],
            ['Outcome', '40% less manual entry'],
            ['Modules', 'HR · Payroll · Claims · Evaluation'],
            ['Delivery', 'Multi-device layouts and reporting'],
        ],
    ],
    [
        'title' => 'Resort Booking',
        'card_heading' => 'Resort|Booking',
        'kicker' => 'Modern web product',
        'meta' => 'Next.js · Supabase',
        'accent' => 'outline',
        'category' => 'Web application',
        'summary' => 'A clear path from search to stay.',
        'body' => '<p>A responsive cottage booking application built around availability, clear '
            . 'choices, and a streamlined reservation flow, with architecture ready for '
            . 'Supabase-backed authentication and data.</p>',
        'link_url' => 'https://github.com/denzyodfm/beach-resort-booking',
        'link_label' => 'View repository',
        'facts' => [
            ['Frontend', 'Next.js · React · TypeScript'],
            ['Interface', 'Tailwind CSS · Responsive UI'],
            ['Data', 'Supabase-ready APIs'],
            ['Focus', 'Booking clarity and conversion'],
        ],
    ],
    [
        'title' => 'Real-Time Alerts',
        'card_heading' => 'Real-Time|Alerts',
        'kicker' => 'Automated communication',
        'meta' => 'SMS · Email · API',
        'accent' => 'outline-cobalt',
        'category' => 'Integration',
        'summary' => 'The right message at the right time.',
        'body' => '<p>Instant and scheduled client and staff notifications sent from FileMaker '
            . 'workflows across SMS, email, MMS, and Telegram delivery channels.</p>',
        'link_url' => null,
        'link_label' => null,
        'facts' => [
            ['Channels', 'SMS · Email · MMS · Telegram'],
            ['Integration', 'Globe M360 · SMTP · Telegram API'],
            ['Modes', 'Real-time and scheduled'],
            ['Source', 'Operational FileMaker events'],
        ],
    ],
    [
        'title' => 'Sales & Inventory Management', 'card_heading' => 'Sales &|Inventory',
        'kicker' => 'Inventory operations', 'meta' => 'FileMaker · Reporting', 'accent' => 'ink',
        'category' => 'Operations', 'summary' => 'Real-time visibility from order to replenishment.',
        'body' => '<p>End-to-end sales order and inventory tracking with current stock levels, replenishment workflows, and automated low-stock alerts.</p>',
        'link_url' => null, 'link_label' => null,
        'facts' => [['Outcome','Real-time stock visibility'],['Scope','Sales · Inventory · Replenishment'],['Stack','FileMaker · Dashboards'],['Automation','Low-stock alerts']],
    ],
    [
        'title' => 'Billing & Collection System', 'card_heading' => 'Billing &|Collection',
        'kicker' => 'Financial operations', 'meta' => 'Invoices · Aging', 'accent' => 'outline',
        'category' => 'Finance', 'summary' => 'Clearer receivables and faster follow-up.',
        'body' => '<p>Automated invoicing, payment tracking, aging reports, and collection workflows built for dependable daily use.</p>',
        'link_url' => 'https://github.com/denzyodfm/chapel-collection-system', 'link_label' => 'View related repository',
        'facts' => [['Outcome','25% less follow-up time'],['Scope','Billing and collections'],['Reporting','Invoices · Payments · Aging'],['Control','Traceable records']],
    ],
    [
        'title' => 'Request & Approval with Liquidation', 'card_heading' => 'Request &|Liquidation',
        'kicker' => 'Approval workflows', 'meta' => 'Digital workflow', 'accent' => 'cobalt',
        'category' => 'Workflow', 'summary' => 'Requests and expenses with a complete audit trail.',
        'body' => '<p>Digital requests, multi-level approvals, expense liquidation, attachments, and audit history in one workflow.</p>',
        'link_url' => 'https://github.com/denzyodfm/sfxc-activity-request', 'link_label' => 'View repository',
        'facts' => [['Workflow','Request to liquidation'],['Approval','Multi-level'],['Records','Attachments · Audit trail'],['Outcome','Less paper handling']],
    ],
    [
        'title' => 'Pawnshop Inventory System', 'card_heading' => 'Pawnshop|Inventory',
        'kicker' => 'Branch inventory', 'meta' => 'FileMaker · Multi-branch', 'accent' => 'outline-cobalt',
        'category' => 'Inventory', 'summary' => 'Accurate appraisal and redemption records.',
        'body' => '<p>Tracks pawned items, appraisals, redemption history, valuation, and stock reconciliation across multiple branches.</p>',
        'link_url' => null, 'link_label' => null,
        'facts' => [['Scope','Multi-branch inventory'],['Records','Appraisals · Redemptions'],['Outcome','Accurate reconciliation'],['Stack','FileMaker · Reporting']],
    ],
    [
        'title' => 'Fuel Monitoring', 'card_heading' => 'Fuel|Monitoring',
        'kicker' => 'Operations monitoring', 'meta' => 'Web application', 'accent' => 'ink',
        'category' => 'Web application', 'summary' => 'Operational fuel data in one focused system.',
        'body' => '<p>A dedicated application for recording, reviewing, and managing fuel-related operational data.</p>',
        'link_url' => 'https://github.com/denzyodfm/fuel-monitoring', 'link_label' => 'View repository',
        'facts' => [['Purpose','Fuel usage monitoring'],['Scope','Operational data'],['Delivery','Web application'],['Source','GitHub project']],
    ],
    [
        'title' => 'Zyeon Tire Trading', 'card_heading' => 'Tire|Trading',
        'kicker' => 'Trading operations', 'meta' => 'Web application', 'accent' => 'outline',
        'category' => 'Web application', 'summary' => 'A purpose-built tire trading workflow.',
        'body' => '<p>A focused business application supporting tire trading records and day-to-day operations.</p>',
        'link_url' => 'https://github.com/denzyodfm/zyeon-tire-trading', 'link_label' => 'View repository',
        'facts' => [['Purpose','Trading records'],['Scope','Day-to-day operations'],['Delivery','Web application'],['Source','GitHub project']],
    ],
];

try {
    $pdo = Database::connect();
    $content = new Content($pdo);

    $typeIds = [];
    foreach ($types as $type) {
        $existing = $content->typeBySlug(strtolower($type['name']));
        if ($existing !== null) {
            $typeIds[$type['name']] = (int) $existing['id'];
            echo "Type already present: {$type['name']}\n";
            continue;
        }
        $typeIds[$type['name']] = $content->createType(
            $type['name'],
            $type['placement'],
            $type['sort_order']
        );
        echo "Created type: {$type['name']} ({$type['placement']})\n";
    }

    $order = 0;
    foreach ($projects as $project) {
        $facts = $project['facts'];
        unset($project['facts']);

        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $project['title']), '-'));
        $existing = $content->entryBySlug($slug, false);

        if ($existing !== null && !$force) {
            echo "Entry already present: {$project['title']}\n";
            $order++;
            continue;
        }

        $payload = $project + [
            'type_id' => $typeIds['Project'],
            'status' => 'published',
            'published_at' => gmdate('Y-m-d'),
            'sort_order' => $order,
        ];

        if ($existing !== null) {
            $content->updateEntry((int) $existing['id'], $payload);
            $id = (int) $existing['id'];
            echo "Rewrote entry: {$project['title']}\n";
        } else {
            $id = $content->createEntry($payload);
            echo "Created entry: {$project['title']}\n";
        }
        $content->replaceFacts($id, array_map(
            static fn(array $f): array => ['label' => $f[0], 'value' => $f[1]],
            $facts
        ));
        $order++;
    }

    $cards = count($content->portfolioCards());
    echo "\n{$cards} published project card(s) ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Seeding failed: ' . $e->getMessage() . "\n");
    exit(1);
}
