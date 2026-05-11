<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ProductRepository;

final class ProductController extends Controller
{
  /** @param array<string, string> $params */
  public function show(Request $request, array $params): void
  {
    $id = (int) ($params['id'] ?? 0);
    $product = (new ProductRepository())->find($id);

    if ($product === null) {
      Response::abort(404, 'Product not found');
    }

    $this->view('home/product-show', [
      'title' => 'Product | ' . $product->name,
      'product' => $product,
    ]);
  }
}
