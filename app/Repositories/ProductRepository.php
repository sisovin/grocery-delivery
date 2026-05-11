<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Product;
use PDOException;

final class ProductRepository
{
  /** @return array<int, Product> */
  public function all(int $limit = 10): array
  {
    try {
      $stmt = Database::pdo()->prepare(
        'SELECT id, name, origin, deal, price, description, is_organic, province FROM products ORDER BY id ASC LIMIT :limit'
      );
      $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
      $stmt->execute();

      $rows = $stmt->fetchAll();
      return array_map(static fn(array $row): Product => Product::fromArray($row), $rows);
    } catch (PDOException) {
      return $this->fallback();
    }
  }

  public function find(int $id): ?Product
  {
    try {
      $stmt = Database::pdo()->prepare(
        'SELECT id, name, origin, deal, price, description, is_organic, province FROM products WHERE id = :id LIMIT 1'
      );
      $stmt->execute(['id' => $id]);
      $row = $stmt->fetch();

      return is_array($row) ? Product::fromArray($row) : null;
    } catch (PDOException) {
      foreach ($this->fallback() as $item) {
        if ($item->id === $id) {
          return $item;
        }
      }
      return null;
    }
  }

  /** @return array<int, Product> */
  private function fallback(): array
  {
    return [
      new Product(1, 'ប្រអប់ប៉េងប៉ោះ Heirloom', 'Johnson Family Farm', 'Subscribe & Save 15%', 6.50, 'Farm-fresh ប៉េងប៉ោះស្រស់ៗ សម្រាប់សម្ល និងសាឡាដ។', true, 'កណ្ដាល'),
      new Product(2, 'កញ្ចប់អាវ៉ូកាដូសរីរាង្គ', 'ភ្នំដំរី', 'បញ្ចុះតម្លៃ 20%', 8.00, 'Certified organic អាវ៉ូកាដូប្រមូលព្រឹកនេះ។', true, 'មណ្ឌលគីរី'),
      new Product(3, 'ស្វាយរដូវកាលសុវណ្ណ', 'តាកែវ', null, 5.75, 'Seasonal ស្វាយផ្អែមឈ្ងុយ សម្រាប់គ្រួសារ។', true, 'តាកែវ'),
      new Product(4, 'បន្លែបៃតងកាត់ថ្មី', 'បាត់ដំបង', 'Farm-Fresh Daily', 4.25, 'Local farms បន្លែសម្រាប់ម្ហូបប្រចាំថ្ងៃ។', true, 'បាត់ដំបង'),
    ];
  }
}
