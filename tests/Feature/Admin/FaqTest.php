<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The FAQ is the plant's own list: it writes the questions, writes the answers,
 * orders them and removes the ones that stop being asked. Nothing about it is
 * fixed in the code any more — there are no groups to file a question under and
 * no shipped questions to work around.
 */
class FaqTest extends TestCase
{
    use RefreshDatabase;

    private function faq(array $overrides = []): Faq
    {
        return Faq::create(array_merge([
            'question' => ['fa' => 'قیمت لیکا چند است؟', 'en' => 'What does LECA cost?', 'ar' => 'كم سعر ليكا؟'],
            'answer' => ['fa' => 'برای قیمت تماس بگیرید.', 'en' => 'Call for pricing.', 'ar' => 'اتصل للسعر.'],
            'position' => 1,
            'is_active' => true,
        ], $overrides));
    }

    public function test_the_site_ships_with_no_questions(): void
    {
        $this->assertSame(0, Faq::count());

        $this->get('/fa/faq')->assertOk()->assertSee(__('common.no_results'));
    }

    public function test_an_editor_can_add_a_question_and_it_reaches_the_page(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/faqs', [
            'question' => ['fa' => 'حداقل سفارش چقدر است؟', 'en' => 'Minimum order?', 'ar' => 'أقل كمية؟'],
            'answer' => ['fa' => 'یک کامیون.', 'en' => 'One truckload.', 'ar' => 'حمولة شاحنة.'],
            'position' => 1,
            'is_active' => '1',
        ])->assertRedirect();

        $this->get('/fa/faq')->assertOk()->assertSee('حداقل سفارش چقدر است؟');
        $this->get('/en/faq')->assertOk()->assertSee('Minimum order?');
    }

    public function test_an_editor_can_delete_a_question(): void
    {
        $faq = $this->faq();

        $this->actingAs($this->makeAdmin())
            ->delete('/admin/faqs/'.$faq->id)
            ->assertRedirect();

        $this->assertSame(0, Faq::count());
        $this->get('/fa/faq')->assertOk()->assertDontSee('قیمت لیکا چند است؟');
    }

    /** Position is the only thing arranging the page now. */
    public function test_position_orders_the_list(): void
    {
        $this->faq(['position' => 2, 'question' => ['fa' => 'پرسش دوم', 'en' => 'Second', 'ar' => 'الثاني']]);
        $this->faq(['position' => 1, 'question' => ['fa' => 'پرسش نخست', 'en' => 'First', 'ar' => 'الأول']]);

        $html = $this->get('/fa/faq')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'پرسش دوم'),
            strpos($html, 'پرسش نخست'),
            'The lower position should come first.',
        );
    }

    public function test_an_unpublished_question_stays_off_the_page(): void
    {
        $this->faq(['is_active' => false]);

        $this->get('/fa/faq')->assertOk()->assertDontSee('قیمت لیکا چند است؟');
    }

    /** The answers are what Google shows under the search result. */
    public function test_questions_are_published_as_structured_data(): void
    {
        $this->faq();

        $this->get('/fa/faq')->assertSee('"@type":"FAQPage"', false);
    }

    public function test_a_question_needs_both_halves(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/faqs', ['question' => ['fa' => 'بدون پاسخ'], 'is_active' => '1'])
            ->assertSessionHasErrors('answer.fa');

        $this->assertSame(0, Faq::count());
    }
}
