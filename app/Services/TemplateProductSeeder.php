<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
final class TemplateProductSeeder
{
  /** @param array<int, string> $templatePaths */
  public function seed(array $templatePaths): array
  {
    $templatePaths = array_values(array_unique($templatePaths));

    $jsProducts = [];
    $homeCards = [];
    $logs = [];

    foreach ($templatePaths as $path) {
      $templateName = basename($path);
      $templateLogs = [];
      $expectsJsProducts = true;

      if (!is_file($path)) {
        $logs[] = [
          'template' => $templateName,
          'status' => 'error',
          'messages' => ['Template file not found.'],
        ];
        continue;
      }

      $content = (string) file_get_contents($path);
      if ($content === '') {
        $logs[] = [
          'template' => $templateName,
          'status' => 'error',
          'messages' => ['Template content is empty or unreadable.'],
        ];
        continue;
      }

      if (str_contains($path, '01-Home-Template')) {
        $expectsJsProducts = false;
        $homeExtract = $this->extractHomeCards($content);
        $homeCards = $homeExtract['cards'];
        $templateLogs = array_merge($templateLogs, $homeExtract['logs']);

        if (count($homeCards) !== 10) {
          $templateLogs[] = sprintf('Expected 10 home product cards, found %d.', count($homeCards));
        }
      }

      $jsExtract = $this->extractJsProducts($content, $templateName, $expectsJsProducts);
      $templateLogs = array_merge($templateLogs, $jsExtract['logs']);

      foreach ($jsExtract['products'] as $product) {
        $key = $this->slug($product['headline'] !== '' ? $product['headline'] : $product['name']);
        if ($key === '') {
          $key = $this->slug($product['name']);
        }

        if ($key !== '') {
          $jsProducts[$key] = $product;
        }
      }

      $logs[] = [
        'template' => $templateName,
        'status' => $this->hasErrorLogs($templateLogs) ? 'error' : 'ok',
        'messages' => $templateLogs === [] ? ['Validated successfully.'] : $templateLogs,
      ];
    }

    $rows = [];

    foreach ($homeCards as $index => $card) {
      $key = $this->slug($card['headline']);
      $match = $jsProducts[$key] ?? null;

      $name = $match['name'] ?? $this->nameFromBlurb($card['blurb']);
      $origin = $match['origin'] ?? 'Local farms';
      $price = isset($match['price']) ? (float) $match['price'] : (float) (3.50 + ($index * 0.85));
      $province = $this->provinceFromOrigin($origin);

      $rows[] = [
        'name' => $name,
        'origin' => $origin,
        'province' => $province,
        'deal' => $card['deal'] !== '' ? $card['deal'] : ($match['deal'] ?? null),
        'description' => $card['blurb'],
        'price' => round($price, 2),
        'is_organic' => $this->isOrganic($card['blurb']) ? 1 : 0,
      ];
    }

    if ($rows === []) {
      foreach ($jsProducts as $product) {
        $rows[] = [
          'name' => $product['name'],
          'origin' => $product['origin'] !== '' ? $product['origin'] : 'Local farms',
          'province' => $this->provinceFromOrigin($product['origin']),
          'deal' => $product['deal'] !== '' ? $product['deal'] : null,
          'description' => $product['blurb'] !== '' ? $product['blurb'] : $product['headline'],
          'price' => (float) ($product['price'] ?? 0),
          'is_organic' => $this->isOrganic($product['blurb']) ? 1 : 0,
        ];
      }
    }

    if ($rows === []) {
      $logs[] = [
        'template' => 'seed:templates',
        'status' => 'error',
        'messages' => ['No valid products could be extracted from template files.'],
      ];

      return [
        'imported' => 0,
        'failed' => true,
        'error' => 'No valid products were extracted.',
        'logs' => $logs,
        'stats' => [
          'home_cards' => count($homeCards),
          'js_products' => count($jsProducts),
        ],
      ];
    }

    $imported = 0;
    $failed = false;
    $error = null;

    try {
      $pdo = Database::pdo();
      $pdo->beginTransaction();

      $pdo->exec('DELETE FROM products');
      $pdo->exec('ALTER TABLE products AUTO_INCREMENT = 1');

      $stmt = $pdo->prepare(
        'INSERT INTO products (name, origin, province, deal, description, price, is_organic)
         VALUES (:name, :origin, :province, :deal, :description, :price, :is_organic)'
      );

      foreach ($rows as $row) {
        $stmt->execute($row);
        $imported++;
      }

      $pdo->commit();
    } catch (\Throwable $exception) {
      if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
      }

      $failed = true;
      $error = $exception->getMessage();
      $logs[] = [
        'template' => 'database',
        'status' => 'error',
        'messages' => ['Import failed: ' . $exception->getMessage()],
      ];
    }

    return [
      'imported' => $imported,
      'failed' => $failed,
      'error' => $error,
      'logs' => $logs,
      'stats' => [
        'home_cards' => count($homeCards),
        'js_products' => count($jsProducts),
      ],
    ];
  }

  /** @return array{cards:array<int, array{headline:string,blurb:string,deal:string}>, logs:array<int, string>} */
  private function extractHomeCards(string $html): array
  {
    $cards = [];
    $logs = [];

    if (!preg_match_all('/<article class="product-card">(.*?)<\/article>/su', $html, $matches)) {
      return ['cards' => [], 'logs' => ['No <article class="product-card"> sections found in home template.']];
    }

    foreach ($matches[1] as $index => $chunk) {
      $headline = $this->captureHtml($chunk, '/<h3 class="headline">(.*?)<\/h3>/su');
      $blurb = $this->captureHtml($chunk, '/<p class="blurb">(.*?)<\/p>/su');
      $deal = $this->captureHtml($chunk, '/<span class="deal-tag">(.*?)<\/span>/su');

      if ($headline === '' || $blurb === '') {
        $logs[] = sprintf('Home card #%d skipped: missing headline or blurb.', $index + 1);
        continue;
      }

      $cards[] = [
        'headline' => $headline,
        'blurb' => $blurb,
        'deal' => $deal,
      ];
    }

    return ['cards' => $cards, 'logs' => $logs];
  }

  /** @return array{products:array<int, array{name:string,origin:string,deal:string,headline:string,blurb:string,price:float}>, logs:array<int, string>} */
  private function extractJsProducts(string $content, string $templateName, bool $required): array
  {
    $products = [];
    $logs = [];

    if (!preg_match('/const\s+products\s*=\s*\[(.*?)\];/su', $content, $arrayMatch)) {
      if (!$required) {
        return ['products' => [], 'logs' => ['JavaScript products array not required for this template.']];
      }

      return [
        'products' => [],
        'logs' => [sprintf('No JavaScript products array found in %s.', $templateName)],
      ];
    }

    $arrayBody = $arrayMatch[1];

    if (!preg_match_all('/\{\s*id\s*:\s*\d+.*?\n\s*\},?/su', $arrayBody, $objects)) {
      return [
        'products' => [],
        'logs' => [sprintf('Products array exists but no valid product objects found in %s.', $templateName)],
      ];
    }

    foreach ($objects[0] as $index => $objectChunk) {
      $name = $this->captureObject($objectChunk, 'name');
      $origin = $this->captureObject($objectChunk, 'origin');
      $deal = $this->captureObject($objectChunk, 'deal');
      $headline = $this->captureObject($objectChunk, 'headline');
      $blurb = $this->captureObject($objectChunk, 'blurb');

      if ($name === '' || $headline === '') {
        $logs[] = sprintf('%s product #%d skipped: missing required fields name/headline.', $templateName, $index + 1);
        continue;
      }

      $price = 0.0;
      if (preg_match('/price\s*:\s*([0-9]+(?:\.[0-9]+)?)/u', $objectChunk, $priceMatch)) {
        $price = (float) $priceMatch[1];
      }

      $products[] = [
        'name' => $name,
        'origin' => $origin,
        'deal' => $deal,
        'headline' => $headline,
        'blurb' => $blurb,
        'price' => $price,
      ];

      if ($origin === '') {
        $logs[] = sprintf('%s product "%s" missing origin; fallback origin will be used.', $templateName, $name);
      }

      if ($blurb === '') {
        $logs[] = sprintf('%s product "%s" missing blurb; headline will be used as description.', $templateName, $name);
      }
    }

    if (count($products) !== 10) {
      $logs[] = sprintf('%s contains %d valid JS products (expected 10).', $templateName, count($products));
    }

    return ['products' => $products, 'logs' => $logs];
  }

  private function captureObject(string $objectChunk, string $field): string
  {
    if (preg_match('/' . preg_quote($field, '/') . '\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/u', $objectChunk, $match)) {
      return stripcslashes(trim($match[1]));
    }

    return '';
  }

  private function captureHtml(string $chunk, string $regex): string
  {
    if (!preg_match($regex, $chunk, $match)) {
      return '';
    }

    $value = html_entity_decode(strip_tags($match[1]), ENT_QUOTES, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
  }

  private function nameFromBlurb(string $blurb): string
  {
    $parts = preg_split('/\s+(នាំមក|ប្រមូល|ជម្រើស|រួមមាន)/u', $blurb, 2);
    $name = trim((string) ($parts[0] ?? ''));

    if ($name === '') {
      return 'ផលិតផលសរីរាង្គ';
    }

    return mb_substr($name, 0, 190);
  }

  private function provinceFromOrigin(string $origin): string
  {
    $origin = trim($origin);

    $map = [
      'Johnson Family Farm' => 'កណ្ដាល',
      'ភ្នំដំរី' => 'មណ្ឌលគីរី',
      'កំពត' => 'កំពត',
      'តាកែវ' => 'តាកែវ',
      'ត្បូងឃ្មុំ' => 'ត្បូងឃ្មុំ',
      'បាត់ដំបង' => 'បាត់ដំបង',
      'ភ្នំក្រវាញ' => 'កោះកុង',
      'វាលស្មៅ' => 'កំពង់ធំ',
      'ខ្ពង់រាប' => 'មណ្ឌលគីរី',
      'កំពង់ចាម' => 'កំពង់ចាម',
    ];

    if (isset($map[$origin])) {
      return $map[$origin];
    }

    if (str_contains($origin, '/')) {
      $first = trim((string) explode('/', $origin)[0]);
      return $map[$first] ?? $first;
    }

    if ($origin !== '' && preg_match('/\p{Khmer}/u', $origin) === 1) {
      return mb_substr($origin, 0, 120);
    }

    return 'ភ្នំពេញ';
  }

  private function slug(string $value): string
  {
    $value = mb_strtolower(trim($value));
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    return trim($value);
  }

  private function isOrganic(string $text): bool
  {
    $text = mb_strtolower($text);
    return str_contains($text, 'organic') || str_contains($text, 'សរីរាង្គ');
  }

  /** @param array<int, string> $logs */
  private function hasErrorLogs(array $logs): bool
  {
    foreach ($logs as $message) {
      if (str_contains($message, 'No ') || str_contains($message, 'skipped') || str_contains($message, 'expected')) {
        return true;
      }
    }

    return false;
  }
}
