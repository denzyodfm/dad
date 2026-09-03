<?php
declare(strict_types=1);

namespace App;

/**
 * The public portfolio. The project cards and their detail panels come from
 * the content tables; everything else is fixed copy.
 */

require_once __DIR__ . '/app/bootstrap.php';

$content = new Content($pdo);
$cards = $content->portfolioCards();
$writing = $content->writingEntries();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Dennis Dizon builds secure, fast web applications and FileMaker business systems, using AI-assisted development, and runs the infrastructure behind them." />
  <meta name="theme-color" content="#f2f0e9" />
  <title>Dennis Dizon — Web Application &amp; FileMaker Developer</title>
  <link rel="icon" href="favicon.svg" type="image/svg+xml" />

  <!-- Sharing. Paths are root-absolute; see README to switch them to an
       absolute origin, which Facebook's scraper requires. -->
  <meta property="og:type" content="profile" />
  <meta property="og:title" content="Dennis Dizon — Web Application &amp; FileMaker Developer" />
  <meta property="og:description" content="Dennis Dizon builds secure, fast web applications and FileMaker business systems, using AI-assisted development, and runs the infrastructure behind them." />
  <meta property="og:image" content="output/og-image.png" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />
  <meta property="og:image:alt" content="Dennis Dizon — business systems, without the friction. 40% less manual entry, 30% faster processing, 99% uptime, 10+ years in IT." />
  <meta property="og:locale" content="en_PH" />
  <meta property="profile:first_name" content="Dennis" />
  <meta property="profile:last_name" content="Dizon" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Dennis Dizon — Web Application &amp; FileMaker Developer" />
  <meta name="twitter:description" content="Dennis Dizon builds secure, fast web applications and FileMaker business systems, using AI-assisted development, and runs the infrastructure behind them." />
  <meta name="twitter:image" content="output/og-image.png" />
  <meta name="twitter:image:alt" content="Dennis Dizon — business systems, without the friction. 40% less manual entry, 30% faster processing, 99% uptime, 10+ years in IT." />

  <link rel="preload" href="fonts/manrope-400-700-latin.woff2" as="font" type="font/woff2" crossorigin />
  <link rel="stylesheet" href="fonts/fonts.css" />
  <link rel="stylesheet" href="styles.css" />
  <script src="script.js" defer></script>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Person",
    "name": "Dennis Antonida Dizon",
    "alternateName": "Dennis Dizon",
    "jobTitle": "Web Application Developer / FileMaker Developer / IT Specialist",
    "description": "Web application and FileMaker developer building secure, fast applications with AI-assisted development, and running the infrastructure behind them.",
    "email": "mailto:denzyodfm@gmail.com",
    "telephone": "+63-909-599-4462",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Butuan City",
      "addressCountry": "PH"
    },
    "sameAs": ["https://github.com/denzyodfm"],
    "alumniOf": [
      { "@type": "CollegeOrUniversity", "name": "Caraga State University" },
      { "@type": "CollegeOrUniversity", "name": "University of Cebu" }
    ],
    "knowsAbout": [
      "Web Application Development", "Next.js", "React", "TypeScript", "Tailwind CSS",
      "PHP", "MySQL", "Supabase", "AI-assisted development",
      "Claris FileMaker", "FileMaker WebDirect", "FileMaker Data API",
      "REST APIs", "SQL", "PHP", "JavaScript", "Windows Server", "Linux", "Hyper-V"
    ]
  }
  </script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <div class="shell">
    <header class="site-header">
      <a class="brand" href="#main"><span class="brand-mark">DD</span><span>Dennis Dizon</span></a>
      <p class="role">Web &amp; FileMaker Developer / IT Specialist</p>
      <div class="header-actions"><span class="available"><i></i>Available</span><?php if ($writing !== []): ?><a href="writing.php">Writing &#8599;</a><?php endif; ?><a href="output/pdf/Dennis-Dizon-Resume-Professional.pdf" download="Dennis-Dizon-Resume-Professional.pdf" type="application/pdf">Résumé ↓</a></div>
    </header>

    <main id="main">
      <section class="intro" aria-labelledby="intro-title">
        <div><p class="eyebrow">Butuan City, Philippines · 7+ years building custom applications</p><h1 id="intro-title">Business systems,<br /><span>without the friction.</span></h1></div>
        <div class="intro-side">
          <p>I build secure web applications and FileMaker business systems, using AI-assisted development to ship quickly without cutting corners on security, and I keep the infrastructure behind them fast and reliable.</p>
          <div class="inline-buttons"><button type="button" data-dialog="about-dialog">About me ↗</button><button type="button" data-dialog="github-dialog">More on GitHub ↗</button></div>
        </div>
      </section>

      <section class="projects" aria-labelledby="projects-title">
        <div class="section-label"><p class="eyebrow">Selected systems</p><h2 id="projects-title">Work</h2></div>
<?php foreach ($cards as $index => $card): ?>
        <article class="project <?= e($card['accent']) ?>"><div class="card-top"><span><?= sprintf('%02d', $index + 1) ?></span><span><?= e($card['meta']) ?></span></div><div><small><?= e($card['kicker']) ?></small><h3><?= card_heading($card) ?></h3></div><button type="button" data-dialog="entry-<?= (int) $card['id'] ?>" aria-label="Details: <?= e($card['title']) ?>">Details &#8599;</button></article>
<?php endforeach; ?>
      </section>

      <section class="bottom" aria-label="Career overview">
        <div class="metrics"><div><strong>40%</strong><span>less manual entry</span></div><div><strong>30%</strong><span>faster processing</span></div><div><strong>99%</strong><span>uptime</span></div><div><strong>10+</strong><span>years in IT</span></div></div>
        <div class="stack"><p class="eyebrow">Core stack</p><p>Next.js · React · TypeScript · PHP · MySQL · AI-assisted delivery · FileMaker · REST/JSON · Windows/Linux</p></div>
        <a class="contact" href="mailto:denzyodfm@gmail.com"><span>Have a system in mind?</span><strong>Let's talk ↗</strong></a>
      </section>
    </main>

    <footer><span>© 2026 Dennis Dizon</span><a href="tel:+639095994462"><b>Phone</b> +63 909 599 4462</a><a href="mailto:denzyodfm@gmail.com"><b>Email</b> denzyodfm@gmail.com</a></footer>
  </div>

  <dialog id="about-dialog" aria-labelledby="about-dialog-title"><button class="close" data-close aria-label="Close">×</button><p class="eyebrow">Profile</p><h2 id="about-dialog-title">Applications and infrastructure, together.</h2><p>I'm a results-driven developer with 7+ years delivering end-to-end applications across the modern web and the FileMaker platform, and more than a decade in IT infrastructure. Most of my public work is web applications built with Next.js, React, TypeScript and PHP/MySQL. I lean on AI-assisted development to move from requirement to working code quickly, with security review kept firmly in the loop.</p><div class="facts"><div><span>Current role</span><strong>Web Application &amp; FileMaker Developer / IT Specialist</strong></div><div><span>Education</span><strong>MS Information Technology</strong></div><div><span>Specialty</span><strong>Secure web applications and multi-user business systems</strong></div><div><span>Location</span><strong>Butuan City, Philippines</strong></div></div><a class="dialog-action" href="output/pdf/Dennis-Dizon-Resume-Professional.pdf" download="Dennis-Dizon-Resume-Professional.pdf" type="application/pdf">Download résumé ↓</a></dialog>
<?php foreach ($cards as $index => $card): ?>
  <dialog id="entry-<?= (int) $card['id'] ?>" aria-labelledby="entry-<?= (int) $card['id'] ?>-title"><button class="close" data-close aria-label="Close">&times;</button><p class="eyebrow"><?= sprintf('%02d', $index + 1) ?> / <?= e($card['title']) ?></p><h2 id="entry-<?= (int) $card['id'] ?>-title"><?= e($card['summary']) ?></h2><?= safe_html($card['body']) ?><?php if ($card['facts'] !== []): ?><div class="facts"><?php foreach ($card['facts'] as $fact): ?><div><span><?= e($fact['label']) ?></span><strong><?= e($fact['value']) ?></strong></div><?php endforeach; ?></div><?php endif; ?><?php if (!empty($card['cover_path'])): ?><img class="entry-cover" src="output/uploads/<?= e($card['cover_path']) ?>" alt="<?= e($card['cover_alt']) ?>" loading="lazy" /><?php endif; ?><?php if (!empty($card['media_path'])): ?><?= media_player($card) ?><?php endif; ?><?php if (!empty($card['link_url'])): ?><a class="dialog-action" href="<?= e($card['link_url']) ?>" target="_blank" rel="noreferrer"><?= e($card['link_label'] ?: 'Open link') ?> &#8599;</a><?php endif; ?></dialog>
<?php endforeach; ?>
  <dialog id="github-dialog" aria-labelledby="github-dialog-title"><button class="close" data-close aria-label="Close">×</button><p class="eyebrow">Distinct GitHub projects</p><h2 id="github-dialog-title">More systems in the workshop.</h2><p>Additional public projects selected without repeating the lending and booking applications already featured above.</p><div class="repo-list"><a href="https://github.com/denzyodfm/fuel-monitoring" target="_blank" rel="noreferrer"><span>Fuel Monitoring</span><b>Operations dashboard ↗</b></a><a href="https://github.com/denzyodfm/sfxc-activity-request" target="_blank" rel="noreferrer"><span>SFXC Activity Request</span><b>Request workflow ↗</b></a><a href="https://github.com/denzyodfm/chapel-collection-system" target="_blank" rel="noreferrer"><span>Chapel Collection System</span><b>Collection tracking ↗</b></a><a href="https://github.com/denzyodfm/zyeon-tire-trading" target="_blank" rel="noreferrer"><span>Zyeon Tire Trading</span><b>Trading system ↗</b></a></div><a class="dialog-action" href="https://github.com/denzyodfm" target="_blank" rel="noreferrer">Open GitHub profile ↗</a></dialog>
</body>
</html>
