<?php

namespace Drupal\media_entity_download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\media\MediaInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * DownloadController class.
 */
class DownloadController extends ControllerBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * DownloadController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request object.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Serves the file upon request.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A valid media object.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Serve the file as the response.
   *
   * @throws \Exception
   * @throws NotFoundHttpException
   */
  public function download(MediaInterface $media) {
    $bundle = $media->bundle();
    $source = $media->getSource();
    $config = $source->getConfiguration();
    $field = $config['source_field'];

    // This type has no source field configuration.
    if (!$field) {
      throw new \Exception("No source field configured for the {$bundle} media type.");
    }

    // If a delta was provided, use that.
    $delta = $this->requestStack->getCurrentRequest()->query->get('delta');

    // Get the ID of the requested file by its field delta.
    if (is_numeric($delta)) {
      $values = $media->{$field}->getValue();

      if (isset($values[$delta])) {
        $fid = $values[$delta]['target_id'];
      }
      else {
        throw new NotFoundHttpException("The requested file could not be found.");
      }
    }
    else {
      $fid = $media->{$field}->target_id;
    }

    // If media has no file item.
    if (!$fid) {
      throw new NotFoundHttpException("The media item requested has no file referenced/uploaded in the {$field} field.");
    }

    $file = $this->entityTypeManager()->getStorage('file')->load($fid);

    // Or file entity could not be loaded.
    if (!$file) {
      throw new \Exception("File id {$fid} could not be loaded.");
    }

    $uri = $file->getFileUri();
    $filename = $file->getFilename();

    // Or item does not exist on disk.
    if (!file_exists($uri)) {
      throw new NotFoundHttpException("The file {$uri} does not exist.");
    }

    $response = new BinaryFileResponse($uri);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );

    return $response;
  }

}
