<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Services\PromptContentService;

final class PromptController extends Controller
{
  public function generate(Request $request): void
  {
    $token = (string) $request->input('_token', '');
    if (!Csrf::validate($token)) {
      $this->json(['error' => 'Invalid CSRF token'], 422);
      return;
    }

    $product = trim((string) $request->input('product', ''));
    $origin = trim((string) $request->input('origin', ''));
    $deal = trim((string) $request->input('deal', ''));

    if ($product === '' || $origin === '') {
      $this->json(['error' => 'Product and origin are required'], 422);
      return;
    }

    $result = (new PromptContentService())->generate($product, $origin, $deal);
    $this->json($result);
  }
}
