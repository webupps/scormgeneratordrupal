<?php

namespace Drupal\Tests\term_merge\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\term_merge\Form\MergeTerms;
use Drupal\Tests\term_merge\Kernel\MergeTermsTestBase;

/**
 * @group term_merge
 */
class MergeTermsTest extends MergeTermsTestBase {

  /**
   * @test
   **/
  public function hasTitleCallback() {
    $sut = $this->createSubjectUnderTest();
    $vocabulary = $this->createVocabulary();

    $expected = new TranslatableMarkup('Merge %vocabulary terms', ['%vocabulary' => $vocabulary->label()]);
    self::assertEquals($expected, $sut->titleCallback($vocabulary));
  }

  /**
   * @test
   **/
  public function vocabularyWithoutTermsReturnsEmptyForm() {
    $vocabulary = $this->createVocabulary();
    $sut = $this->createSubjectUnderTest();

    $actual = $sut->buildForm([], new FormState(), $vocabulary);
    self::assertEquals($this->getEmptyFormExpectation(), $actual);
  }

  /**
   * @test
   **/
  public function vocabularyWithTerms() {
    $vocabulary = $this->createVocabulary();
    $term1 = $this->createTerm($vocabulary);
    $term2 = $this->createTerm($vocabulary);
    $sut = $this->createSubjectUnderTest();

    $actual = $sut->buildForm([], new FormState(), $vocabulary);

    $expected = $this->getEmptyFormExpectation();
    $expected['terms']['#options'][$term1->id()] = $term1->label();
    $expected['terms']['#options'][$term2->id()] = $term2->label();
    self::assertEquals($expected, $actual);
  }

  /**
   * Test data provider for validatesSelectedTerms
   *
   * @return array
   */
  public function validatesSelectedTermsTestDataProvider() {
    $testData['No terms selected'] = [
      'selectedTerms' => [],
      'expectingErrors' => TRUE,
    ];

    $testData['One term selected'] = [
      'selectedTerms' => [1],
      'expectingErrors' => TRUE,
    ];

    $testData['Two terms selected'] = [
      'selectedTerms' => [1, 2],
      'expectingErrors' => FALSE,
    ];

    $testData['three terms selected'] = [
      'selectedTerms' => [1, 2, 3],
      'expectingErrors' => FALSE,
    ];

    return $testData;
  }

  /**
   * @test
   *
   * @dataProvider validatesSelectedTermsTestDataProvider
   *
   * @param array $selectedTerms
   *   The selected term ids.
   * @param bool $expectingErrors
   *   If a validation error is expected.
   */
  public function validatesSelectedTerms(array $selectedTerms, $expectingErrors) {
    $vocabulary = $this->createVocabulary();
    $this->createTerm($vocabulary);
    $this->createTerm($vocabulary);
    $this->createTerm($vocabulary);
    $sut = $this->createSubjectUnderTest();

    $formState = new FormState();
    $formState->setValue('terms', $selectedTerms);
    $form = $sut->buildForm([], $formState, $vocabulary);

    $sut->validateForm($form, $formState);

    self::assertSame($expectingErrors, !empty($formState->getErrors()));
  }

  /**
   * @test
   **/
  public function redirectsToConfirmationForm() {
    $vocabulary = $this->createVocabulary();
    $sut = $this->createSubjectUnderTest();

    $formState = new FormState();
    $formState->setValue('terms', [1, 2]);
    $form = $sut->buildForm([], $formState, $vocabulary);

    $sut->submitForm($form, $formState);

    $routeName = 'entity.taxonomy_vocabulary.merge_target';
    $routeParameters['taxonomy_vocabulary'] = $vocabulary->id();
    $expected = new Url($routeName, $routeParameters);
    self::assertEquals($expected, $formState->getRedirect());
  }

  /**
   * @test
   **/
  public function setsLocalStorage() {
    $vocabulary = $this->createVocabulary();
    $sut = $this->createSubjectUnderTest();
    $formState = new FormState();
    $expectedTermIds = [1, 2];
    $formState->setValue('terms', $expectedTermIds);
    $form = $sut->buildForm([], $formState, $vocabulary);

    self::assertEmpty($this->privateTempStoreFactory->get('term_merge')->get('terms'));
    $sut->submitForm($form, $formState);

    self::assertEquals($expectedTermIds, $this->privateTempStoreFactory->get('term_merge')->get('terms'));
  }

  /**
   * @return array
   */
  private function getEmptyFormExpectation() {
    return [
      'terms' => [
        '#type' => 'select',
        '#title' => new TranslatableMarkup("Terms to merge"),
        '#options' => [],
        '#empty_option' => new TranslatableMarkup('Select two or more terms to merge together'),
        '#multiple' => TRUE,
        '#required' => TRUE,
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => new TranslatableMarkup('Merge'),
      ],
    ];
  }

  /**
   * @return \Drupal\term_merge\Form\MergeTerms
   */
  private function createSubjectUnderTest() {
    return new MergeTerms($this->entityTypeManager, $this->privateTempStoreFactory);
  }

  /**
   * {@inheritdoc}
   */
  protected function numberOfTermsToSetUp() {
    return 0;
  }
}
