<?php

declare(strict_types=1);

namespace App\Services;

final class PromptContentService
{
  private const KEYWORDS = [
    'Nourish your home',
    'Earth\'s finest',
    'Farm-fresh',
    'Quality you can taste',
    'Convenience you deserve',
    'Local farms',
    'Seasonal',
    'Certified organic',
  ];

  /** @return array<string, mixed> */
  public function generate(string $product, string $origin, string $deal): array
  {
    $pickedKeywords = array_slice(self::KEYWORDS, 0, 3);

    $headline = 'ស្រស់ពីចម្ការ ដល់ផ្ទះអ្នក';

    $blurb = sprintf(
      '%s មកពី %s ជាមួយរសជាតិធម្មជាតិ និងក្លិនស្រស់បែប Farm-fresh។ ជា Certified organic ដែលផ្តល់ Quality you can taste ហើយយើងដឹកជញ្ជូនក្នុងថ្ងៃតែមួយ។ បញ្ជាទិញលើស $20 ទទួលបានដឹកជញ្ជូនឥតគិតថ្លៃ និងការទូទាត់មានសុវត្ថិភាព 100%%។',
      $product,
      $origin
    );

    $trustPoints = [
      'Quality: Certified organic និងស្រស់ពីកសិដ្ឋាន។',
      'Convenience: Same-day delivery និង free delivery លើស $20។',
      'Trust: គាំទ្រ Local farms និងការទូទាត់មានសុវត្ថិភាព។',
    ];

    $cta = sprintf(
      'Nourish your home ជាមួយ Earth\'s finest។ %s',
      $deal !== '' ? 'Deal: ' . $deal : 'បញ្ជាទិញឥឡូវ'
    );

    return [
      'headline' => $headline,
      'blurb' => $blurb,
      'keywords' => $pickedKeywords,
      'trustPoints' => $trustPoints,
      'cta' => $cta,
    ];
  }
}
