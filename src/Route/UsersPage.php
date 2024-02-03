<?php
declare(strict_types = 1);
/***
 * Date 02.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Mapper\Const\Notice;
use Cpsync\Mapper\Tool;
use Cpsync\Mapper\Trait\Common;
use Cpsync\Mapper\Trait\Message;
use Cpsync\Mapper\Trait\Validator;
use Cpsync\Mapper\User;
use Cpsync\Route\Interface\PageView;
use Exception;
use Psr\Http\Message\ResponseInterface as ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Cpsync\Mapper\Trait\BlockUser;

/**
 * UsersPage class
 */
class UsersPage implements PageView
{
	use Validator;
	use Common;
	use Message;
	use BlockUser;

	/**
	 * @var User $user
	 */
	private User $user;

	/**
	 * @var array $access
	 */
	private array $access;

	/**
	 * @var mixed $hash
	 */
	private mixed $hash;

	/**
	 * @var mixed $userSession
	 */
	private mixed $userSession;

	/**
	 * @var mixed $session
	 */
	private mixed $session;

	/**
	 * @var object $database
	 */
	private object $database;

	/**
	 * @var Environment $view
	 */
	private Environment $view;
	private Tool $tool;

	/**
	 * UsersPage constructor.
	 * @param User $user
	 * @param Environment $view
	 * @param Tool $tool
	 * @throws \Exception
	 */
	public function __construct(User $user, Environment $view, Tool $tool)
	{
		$this->user = $user;
		$this->database = $this->user->database;
		$this->session = $this->user->session;
		$this->userSession = $this->session->getData('user');
		$this->hash = $this->session->getData('hash');
		$this->isBanned();
		$this->view = $view;
		$this->access = $this->user->getAccess();
		$this->tool = $tool;
	}

	/**
	 * Execution transfer depending on the request method
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param array $args
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function handleRequest(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
	{
		if($this->userSession == null) {
			return $response->withHeader('Location', '/login')->withStatus(302);
		}
		if($request->getMethod() === 'POST') {
			$this->handlePost($request, $response);
		} elseif($request->getMethod() === 'GET') {
			$this->handleGet($request, $response);
		}
		return $response;
	}

	/**
	 * Get request handler
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 * @throws Exception
	 ***/
	public function handleGet(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$this->session->setData('hash', hash('ripemd160', (string)time()));
		try {
			$body = $this->view->render('users.twig', ['users' => $this->user->getUsers(), 'tools' => $this->tool->getAllTools(), 'UserAddintional' => true, 'session' => $this->session->getSession(), 'user' => $this->userSession, 'roles' => $this->access['role'],]);
		} catch(LoaderError|SyntaxError|RuntimeError $exception) {
			throw new Exception(__LINE__.': '.$exception->getMessage());
		}
		$response->getBody()->write($body);
		return $response;
	}

	/**
	 * POST request handler
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 * @throws Exception
	 ***/
	public function handlePost(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		// Get data from POST request
		$params = (array)$request->getParsedBody();
		if(isset($params['action'], $params['hash'])) {
			if($params['hash'] != $this->hash) {
				$this->setMessage(Notice::W_HACKING_ATTEMPT);
				throw new Exception($this->getMessageJson());
			}
			$this->switchAction($params);
		}
		return $response;
	}

	/**
	 * Handler for adding/ editing/ deleting a user
	 * @param array $params
	 * @return void
	 * @throws Exception
	 */
	private function switchAction(array $params): void
	{
		$action_opts = ['edit' => ['method' => 'editUser', 'access' => 'granted', 'params' => $params, 'error_message' => Notice::W_NO_ACCESS_TO_EDIT], 'delete' => ['method' => 'deleteUser', 'access' => 'superuser', 'params' => $params['user_id'], 'error_message' => Notice::W_NO_ACCESS_TO_DELETE], 'new' => ['method' => 'newUser', 'access' => 'superuser', 'params' => $params, 'error_message' => Notice::W_NO_ACCESS_TO_ADD_USER],];
		$action = $params['action'];
		if(isset($action_opts[$action])) {
			$user_access = $action_opts[$action]['access'];
			if(in_array($this->userSession['user_status'], $this->access[$user_access], true)) {
				$method = $action_opts[$action]['method'];
				$params = $action_opts[$action]['params'];
				$this->$method($params);
			}
			$this->setMessage($action_opts[$action]['error_message']);
			throw new Exception($this->getMessageJson());
		}
	}

	/**
	 * Handler for adding a user
	 * @param $params
	 * @throws Exception
	 */
	private function newUser($params): void
	{
		// Check if all required parameters are set
		if($this->isNewUserParams($params) === true) {
			if($this->isValidRequest($params) === true) {
				$user = $this->user->getUser('user_login', $params['user_login']);
				if(!isset($user['user_id'])) {
					$params['user_pass'] = password_hash($params['password'], PASSWORD_BCRYPT);
					$new_user = $this->user->newUser($params['user_status'], $params['user_name'], $params['user_login'], $params['user_email'], $params['user_pass']);
					if($new_user) {
						$this->setMessage(Notice::N_USER_ADDED, true);
						throw new Exception($this->getMessageJson());
					}
					$this->setMessage(Notice::E_FAILED_USER_ADD);
					throw new Exception($this->getMessageJson());
				}
				$this->setMessage(Notice::N_USER_EXIST);
				throw new Exception($this->getMessageJson());
			}
			$this->setMessage(Notice::W_INVALID_REQUEST);
			throw new Exception($this->getMessageJson());
		}
		$this->setMessage(Notice::W_INVALID_PARAMS);
		throw new Exception($this->getMessageJson());
	}

	/**
	 * Check validity of parameters
	 * @param array $params
	 * @return bool|string
	 */
	private function isValidRequest(array $params): bool|string
	{
		return in_array($params['user_status'], $this->access['role'], true)
			? ($this->isNameValid($params['user_name']) && $this->isSizeValid($params['user_name']) &&
			$this->isSizeValid($params['user_login'])
				? ($this->isLoginValid($params['user_login'])
					?? ( $this->isEmailValid($params['user_email'])
						?? $this->setMessage(Notice::W_INVALID_EMAIL) ))
				: $this->setMessage(Notice::W_INVALID_NAME))
			: $this->setMessage(Notice::W_NO_ACCESS);
	}

	/**
	 * Check if all required parameters are set
	 * @param array $params
	 * @return bool|string
	 */
	private function isNewUserParams(array $params): bool|string
	{
		return isset($params['user_status'], $params['user_name'], $params['user_login'], $params['user_email'], $params['password']) && count($params) === 7 || $this->setMessage(Notice::W_INVALID_PARAMS);
	}


	/**
	 * Check if all required parameters are set for editing
	 * @param $params
	 * @throws Exception
	 * @return void
	 */
	private function editUser($params): void
	{
		if(isset($params['colname'], $params['value'], $params['user_id'])) {
			$colname = $params['colname'];
			$value = $params['value'];
			if(is_numeric($params['user_id'])) {
				$user = $this->user->getUser('user_id', $params['user_id']);
				if($this->checkPrivilege($user, $value)) {
					$this->setMessage(Notice::W_NO_ACCESS);
					throw new Exception($this->getMessageJson());
				} else
					$this->userEditing($colname, $value, $user);
			}
			$this->setMessage(Notice::W_NO_DIGIT);
			throw new Exception($this->getMessageJson());
		}
		$this->setMessage(Notice::W_INVALID_PARAMS);
		throw new Exception($this->getMessageJson());
	}

	/**
	 * Handler for editing a user
	 * @param $colname
	 * @param $value
	 * @param $user
	 * @throws \Exception
	 */
	private function userEditing($colname, $value, $user): void
	{
		if(in_array($colname, $this->access['column'], true) && $user['user_status'] != 'superadmin') {
			if($this->isParamValid($colname, $value)) {
				$this->user->editUser($user['user_id'], $colname, $value);
				$this->setMessage(Notice::N_SAVED, true);
				throw new Exception($this->getMessageJson());
			}
			$this->setMessage(Notice::W_INVALID_PARAMS);
			throw new Exception($this->getMessageJson());
		}
		$this->setMessage(Notice::W_NO_ACCESS);
		throw new Exception($this->getMessageJson());
	}

	/**
	 * Check the validity of the parameters
	 * @param $colname
	 * @param $value
	 * @return bool
	 */
	private function isParamValid($colname, $value): bool
	{
		return match ($colname) {
			'user_status' => in_array($value, $this->access['role'], true),
			'user_name' => ($this->isNameValid($value) && $this->isSizeValid($value)),
			'user_email' => $this->isEmailValid($value),
			default => false,
		};
	}

	/**
	 * Handler for deleting a user
	 * @param $user_id
	 * @throws Exception
	 */
	private function deleteUser($user_id): void
	{
		if(isset($user_id) && is_numeric($user_id)) {
			$user = $this->user->getUser('user_id', $user_id);
			// If not superadmin tries to delete admin
			if(($user['user_status'] == 'admin' && $this->userSession['user_status'] != $this->access['superuser'][0]) || $user['user_status'] == $this->access['superuser'][0]) {
				$this->setMessage(Notice::W_NO_ACCESS);
				throw new Exception($this->getMessageJson());
			} else {
				$this->user->deleteUser((int)$user_id);
			}
		} else {
			$this->setMessage(Notice::W_INVALID_PARAMS);
			throw new Exception($this->getMessageJson());
		}
	}

	/**
	 * Check if the user has the right to edit
	 * @param array $user
	 * @param string $value
	 * @return bool
	 */
	private function checkPrivilege(array $user, string $value): bool
	{
		return (!isset($user['user_id']) // if user not exist
			|| ($this->userSession['user_status'] != $this->access['superuser'][0] // If the editor is not a superadmin
				&& $user['user_status'] == 'admin') // while the editor is an admin
			|| ($this->userSession['user_status'] == 'admin' && $user['user_status'] == 'admin') // OR if the editor and the edited are admins
			|| ($this->userSession['user_status'] == 'admin' && $value == 'admin') // OR if the admin wants to raise the user's status to admin
			|| ($this->userSession['user_status'] == 'admin' && $value == 'superuser') // OR if the admin wants to raise the user's status to superadmin
			|| $user['user_status'] == $this->access['superuser'][0]); // OR if the edited is a superadmin
	}


}