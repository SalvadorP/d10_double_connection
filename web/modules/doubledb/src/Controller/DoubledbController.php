<?php

namespace Drupal\doubledb\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\user\UserAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns JSON of the last 100 “page” nodes from an external DB.
 */
class DoubledbController extends ControllerBase {

  /** @var \Drupal\user\UserAuthInterface */
  protected $userAuth;

  public function __construct(UserAuthInterface $user_auth) {
    $this->userAuth = $user_auth;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.auth')
    );
  }

  public function getLastPages(Request $request) {

    // 1) Basic Auth handling
    $auth_header = $request->headers->get('Authorization');
    if (empty($auth_header) || strpos($auth_header, 'Basic ') !== 0) {
      throw new AccessDeniedHttpException('Missing Basic Auth header.');
    }
    list($user, $pass) = explode(':', base64_decode(substr($auth_header, 6)), 2);
    $uid = $this->userAuth->authenticate($user, $pass);
    if ($uid === FALSE) {
      throw new AccessDeniedHttpException('Invalid credentials.');
    }

    $connection = Database::getConnection('default');

    $replica = $request->get('replica');
    if (!empty($replica)) {
      // 2) Switch to external database
      $connection = Database::getConnection('replica');
    }

    // 3) Query the last 100 pages
    $query = $connection->select('node_field_data', 'n')
      ->fields('n', ['nid', 'title', 'uid', 'created', 'status'])
      ->condition('n.type', 'article')
      ->condition('n.status', 1)
      ->orderBy('n.created', 'DESC')
      ->range(0, 100);
    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    // 4) Tidy up the date & return
    $data = array_map(function($row) {
      return [
        'id'          => (int) $row['nid'],
        'title'       => $row['title'],
        'author_uid'  => (int) $row['uid'],
        'created_at'  => date(DATE_ATOM, $row['created']),
        'published'   => (bool) $row['status'],
      ];
    }, $results);

    Database::setActiveConnection();

    // 5) Hand it back!
    return new JsonResponse($data);
  }

}
