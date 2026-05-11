<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

final class DashboardController extends Controller
{
  public function admin(Request $request): void
  {
    $this->view('dashboard/admin', ['title' => 'Nourish Admin Dashboard']);
  }

  public function customer(Request $request): void
  {
    $this->view('dashboard/customer', ['title' => 'Nourish Customer Dashboard']);
  }

  public function supplier(Request $request): void
  {
    $this->view('dashboard/supplier', ['title' => 'Nourish Supplier Dashboard']);
  }
}
