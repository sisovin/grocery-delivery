<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\ProductRepository;

final class HomeController extends Controller
{
  public function index(Request $request): void
  {
    $products = (new ProductRepository())->all(10);

    $this->view('home/index', [
      'title' => 'Nourish | Farm-fresh Delivery',
      'products' => $products,
      'provinces' => [
        'ភ្នំពេញ',
        'កណ្ដាល',
        'កំពង់ធំ',
        'កំពង់ឆ្នាំង',
        'កំពង់ស្ពឺ',
        'កំពង់ចាម',
        'កំពត',
        'កែប',
        'កោះកុង',
        'ក្រចេះ',
        'មណ្ឌលគីរី',
        'បាត់ដំបង',
        'បន្ទាយមានជ័យ',
        'ប៉ៃលិន',
        'ព្រះវិហារ',
        'ព្រៃវែង',
        'ពោធិ៍សាត់',
        'រតនគីរី',
        'សៀមរាប',
        'ស្ទឹងត្រែង',
        'ស្វាយរៀង',
        'តាកែវ',
        'ត្បូងឃ្មុំ',
        'ឧត្តរមានជ័យ',
      ],
    ]);
  }
}
