<?php
declare(strict_types=1);

/**
 * Router for PHP's built-in server, used only in development.
 *
 * The built-in server has no .htaccess and will happily serve .env, the SQL
 * schema and the build scripts. This applies the same denials that
 * .htaccess and web.config apply in production, so what you see locally
 * matches what a correctly configured host does.
 *
 * Started for you by tools/serve.php.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);

$denied = [
    '#(^|/)\.[^/]#',              // dotfiles: .env, .git, .gitignore
    '#^/(database|tools|tmp)(/|$)#', // schema, build tooling, page renders
    '#\.(sql|py|md)$#i',
];
foreach ($denied as $pattern) {
    if (preg_match($pattern, $path) === 1) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo "404 Not Found\n";
        return true;
    }
}

// Anything else: let the built-in server serve the file or run the script.
return false;
