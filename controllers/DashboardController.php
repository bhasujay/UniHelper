<?php

namespace app\controllers;

require_once dirname(__DIR__, 1) . '/models/User.php';

use app\core\Application;
use app\core\Request;
use app\models\User;

class DashboardController
{
    protected $activeComponent = '';
    protected $user;
    protected $role_data;
    protected $role_title;
    protected $queryParams = [];

    public function __construct()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /UniHelper/home');
            exit;
        }

        // Get the user from database
        $userModel = new User();
        $this->user = $userModel->findById($_SESSION['user_id']);
        
        if (!$this->user) {
            session_destroy();
            header('Location: /UniHelper/register');
            exit;
        }

        switch ($this->user->role) {
            case 'role-applicant':
                $this->role_title = 'Applicant';
                break;
            case 'role-undergrad':
                $this->role_title = 'Undergraduate';
                break;
            case 'role-profile':
                $this->role_title = 'Profile';
                break;
            case 'role-admin':
                $this->role_title = 'Administrator';
                break;
            default:
                $this->role_title = 'Dashboard';
        }

        $this->role_data = require Application::$ROOT_DIR . '/config/sidebar_config.php';
    }

    // Default entry point for dashboard
    public function index()
    {
        // Set default active component
        $this->activeComponent = $this->role_data[$this->user->role][0]['component'];
        
        // Load default component
        $content = $this->loadComponent($this->activeComponent);
        return $this->renderDashboard($content, $this->role_data[$this->user->role]);
    }

    // Renders a specific component based on URL parameter
    public function renderComponent($params)
    {
        $component = $params['component'] ?? $this->role_data[$this->user->role][0]['component'];

        // we should verify that the requested component is actually allowed for the user's role to prevent unauthorized access to components.
        $allowedComponents = array_column($this->role_data[$this->user->role], 'component');
        if (!in_array($component, $allowedComponents)) {
            return $this->renderDashboard("<div class='error'>Unauthorized access to component: {$component}</div>", $this->role_data[$this->user->role]);
        }
        
        
        // Set active component
        $this->activeComponent = $component;

        // Collect all query-string parameters and store them on the instance
        // so they are accessible both server-side (inside included component
        // files) and client-side (via the data-page-params attribute on <main>).
        $request = new Request();
        foreach ($_GET as $key => $value) {
            $this->queryParams[$key] = $request->get($key);
        }

        // Load the requested component
        $content = $this->loadComponent($component, $this->queryParams);
        return $this->renderDashboard($content, $this->role_data[$this->user->role]);
    }

    // Loads a component file
    // $queryParams: associative array of query-string values passed to the
    //               component so server-side component code can use them directly.
    protected function loadComponent($componentName, $queryParams = [])
    {
        $componentPath = Application::$ROOT_DIR . "/views/components/{$componentName}.php";

        if (!file_exists($componentPath)) {
            $componentPath = Application::$ROOT_DIR . "/views/{$componentName}.php";
        }
        
        if (!file_exists($componentPath)) {
            return "<div class='error'>Component {$componentName} not found</div>";
        }

        $user = $this->user;   // Make user available to components
        // $queryParams is already in scope for the included file.
        // Individual params are also extracted into local variables so
        // components can use e.g. $id directly instead of $queryParams['id'].
        extract($queryParams, EXTR_SKIP);
        
        // Capture the component content
        ob_start();
        include $componentPath;
        return ob_get_clean();
    }

    // Renders the dashboard with the given content
    protected function renderDashboard($content = NULL, $sidebar = NULL, $error = NULL)
    {
        $user = $this->user;                        // Make user available to the dashboard template
        $activeComponent = $this->activeComponent;  // Make active component available to the dashboard template
        $role_title = $this->role_title;            // Make role title available to the dashboard template
        $pageParams = $this->queryParams;           // Query-string params forwarded to the view for data-page-params

        // Make content available to the dashboard template
        include Application::$ROOT_DIR . "/views/dashboard.php";
    }

    // Profile card index
    public function profileIndex(Request $request)
    {
        $pathParts = explode('/', $request->getPath());
        $req = end($pathParts);
        $component = '';
        if ($req === 'edit') {
            $component = 'edit-component';
        } elseif ($req === 'view') {
            $component = 'profile-component';
        } elseif ($req === 'change-password') {
            $component = 'change-password-component';
        }

        $activeComponent = $component;
        $content = $this->loadComponent('profile/' . $component);

        return $this->renderDashboard($content, $this->role_data[$this->user->role]);
    }

    // profile edit component action handlers
    public function profileUpdate(Request $request)
    {
        // Get form data
        $this->user->firstName = $request->get('firstName');
        $this->user->lastName = $request->get('lastName');
        $this->user->email = $request->get('email');
        $this->user->phone = $request->get('phone');
        $this->user->public = $request->get('public') ? 1 : 0;
        
        // Role-specific fields
        if ($this->user->role === 'role-applicant') {
            $this->user->alYear = $request->get('alYear');
        } elseif ($this->user->role === 'role-undergrad') {
            $this->user->University = $request->get('undergradUniversity');
            $this->user->major = $request->get('major');
        } elseif ($this->user->role === 'role-profile') {
            $this->user->University = $request->get('profileUniversity');
            $this->user->profileRole = $request->get('profileRole');
        }

        // Handle profile picture upload
        $profilePicture = $request->get('profilePicture');
        if ($profilePicture && isset($profilePicture['tmp_name']) && is_uploaded_file($profilePicture['tmp_name'])) {
            // Delete old profile picture if exists
            if (!empty($this->user->profilePicture)) {
                $oldPicturePath = Application::$ROOT_DIR . '/public' . $this->user->profilePicture;
                if (file_exists($oldPicturePath)) {
                    unlink($oldPicturePath);
                }
            }

            $emailUsername = explode('@', $this->user->email)[0];
            $targetFile = Application::$ROOT_DIR . '/public/uploads/profilePictures/' . $emailUsername . '.' . pathinfo($profilePicture['name'], PATHINFO_EXTENSION);

            if (move_uploaded_file($profilePicture['tmp_name'], $targetFile)) {
                $this->user->profilePicture = '/uploads/profilePictures/' . basename($targetFile);
            }
        } else {
            // If profile picture should be removed (set to null)
            if ($request->get('removeProfilePicture') === '1' || empty($this->user->profilePicture)) {
                // Delete existing profile picture file
                if (!empty($this->user->profilePicture)) {
                    $existingPicturePath = Application::$ROOT_DIR . '/public' . $this->user->profilePicture;
                    if (file_exists($existingPicturePath)) {
                        unlink($existingPicturePath);
                    }
                }
                $this->user->profilePicture = null;
            }
        }

        // Update user
        $error = $this->user->update();

        if ($error instanceof \PDOException) {
            if (strpos($error->getMessage(), 'SQLSTATE[23000]') !== false) {
                $errorMsg = 'Email already in use by another account.';
            } else {
                $errorMsg = 'An error occurred while updating your profile. Please try again.';
            }
            
            $content = $this->loadComponent('profile/edit-component');
            return $this->renderDashboard($content, $this->role_data[$this->user->role], $errorMsg);
        }

        // Redirect to profile view
        header('Location: /UniHelper/profile/view');
        exit;
    }
    
    // Dynamic profile view (e.g., /view/profile/3)
    public function viewProfile($params)
    {
        $this->activeComponent = 'profile-global';

        // Collect any query-string parameters
        $request = new Request();
        foreach ($_GET as $key => $value) {
            $this->queryParams[$key] = $request->get($key);
        }

        // Merge dynamic parameters (like 'id') so they are available in the view
        $this->queryParams = array_merge($this->queryParams, $params);

        // Load the profile global component
        $content = $this->loadComponent('profile/profile-global', $this->queryParams);
        return $this->renderDashboard($content, $this->role_data[$this->user->role]);
    }

}