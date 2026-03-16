import { gql } from '@apollo/client';

// AUTH mutations (LOGIN / LOGOUT / REGISTER) are in queries.js — AuthContext imports from there

export const FORGOT_PASSWORD = gql`
  mutation ForgotPassword($email: String!) {
    forgotPassword(email: $email)
  }
`;

export const RESET_PASSWORD = gql`
  mutation ResetPassword($email: String!, $token: String!, $password: String!, $password_confirmation: String!) {
    resetPassword(email: $email, token: $token, password: $password, password_confirmation: $password_confirmation)
  }
`;


export const UPDATE_PROFILE = gql`
  mutation UpdateProfile($name: String, $email: String, $phone: String) {
    updateProfile(name: $name, email: $email, phone: $phone) {
      id name email phone role status company bio avatar_url
    }
  }
`;

export const UPDATE_PASSWORD = gql`
  mutation UpdatePassword($current_password: String!, $new_password: String!) {
    updatePassword(current_password: $current_password, new_password: $new_password)
  }
`;

export const UPDATE_PREFERENCES = gql`
  mutation UpdatePreferences(
    $timezone: String, $language: String, $theme: String,
    $email_notif: Boolean, $push_notif: Boolean,
    $task_updates: Boolean, $project_updates: Boolean, $weekly_report: Boolean
  ) {
    updatePreferences(
      timezone: $timezone, language: $language, theme: $theme,
      email_notif: $email_notif, push_notif: $push_notif,
      task_updates: $task_updates, project_updates: $project_updates,
      weekly_report: $weekly_report
    ) {
      id user_prefs
    }
  }
`;

// ── Projects ───────────────────────────────────────────────
export const CREATE_PROJECT = gql`
  mutation CreateProject(
    $client_id: String!, $name: String!, $description: String,
    $status: String, $budget: Float, $due_date: String, $developer_ids: [String],
    $is_wordpress: Boolean, $wp_site_url: String
  ) {
    createProject(
      client_id: $client_id, name: $name, description: $description,
      status: $status, budget: $budget, due_date: $due_date, developer_ids: $developer_ids,
      is_wordpress: $is_wordpress, wp_site_url: $wp_site_url
    ) {
      id name status progress budget due_date
      is_wordpress wp_site_url wp_site_id
      client { id name }
      developers { id name }
    }
  }
`;

export const UPDATE_PROJECT = gql`
  mutation UpdateProject(
    $id: String!, $name: String, $description: String, $status: String,
    $progress: Int, $budget: Float, $due_date: String,
    $is_wordpress: Boolean, $wp_site_url: String
  ) {
    updateProject(
      id: $id, name: $name, description: $description, status: $status,
      progress: $progress, budget: $budget, due_date: $due_date,
      is_wordpress: $is_wordpress, wp_site_url: $wp_site_url
    ) {
      id name status progress budget due_date
      is_wordpress wp_site_url wp_site_id
    }
  }
`;

export const DELETE_PROJECT = gql`
  mutation DeleteProject($id: String!) {
    deleteProject(id: $id)
  }
`;

// ── Tasks ──────────────────────────────────────────────────
export const CREATE_TASK = gql`
  mutation CreateTask($project_id: String!, $title: String!, $description: String, $priority: String, $assignee_id: String, $due_date: String) {
    createTask(project_id: $project_id, title: $title, description: $description, priority: $priority, assignee_id: $assignee_id, due_date: $due_date) {
      id title status priority due_date
      project { id name }
      assignee { id name }
    }
  }
`;

export const UPDATE_TASK = gql`
  mutation UpdateTask($id: String!, $title: String, $status: String, $priority: String, $assignee_id: String, $due_date: String, $description: String) {
    updateTask(id: $id, title: $title, status: $status, priority: $priority, assignee_id: $assignee_id, due_date: $due_date, description: $description) {
      id title status priority due_date completed_at
      project { id name }
      assignee { id name }
    }
  }
`;

export const DELETE_TASK = gql`
  mutation DeleteTask($id: String!) {
    deleteTask(id: $id)
  }
`;

export const ADD_TASK_COMMENT = gql`
  mutation AddTaskComment($task_id: String!, $comment: String!) {
    addTaskComment(task_id: $task_id, comment: $comment) {
      id title status
      comments { id content created_at user { id name avatar_url } }
    }
  }
`;


// ── Clients ────────────────────────────────────────────────
export const CREATE_CLIENT = gql`
  mutation CreateClient($name: String!, $email: String!, $company: String, $phone: String, $address: String) {
    createClient(name: $name, email: $email, company: $company, phone: $phone, address: $address) {
      id name email company status revenue joined_at
    }
  }
`;

export const UPDATE_CLIENT = gql`
  mutation UpdateClient($id: String!, $name: String, $email: String, $company: String, $status: String) {
    updateClient(id: $id, name: $name, email: $email, company: $company, status: $status) {
      id name email company status
    }
  }
`;

// ── Users ──────────────────────────────────────────────────
export const CREATE_USER = gql`
  mutation CreateUser($name: String!, $email: String!, $password: String!, $role: String!, $company: String, $phone: String) {
    createUser(name: $name, email: $email, password: $password, role: $role, company: $company, phone: $phone) {
      id name email role status company phone created_at
    }
  }
`;

export const UPDATE_USER = gql`
  mutation UpdateUser($id: String!, $name: String, $email: String, $role: String, $status: String, $company: String, $phone: String) {
    updateUser(id: $id, name: $name, email: $email, role: $role, status: $status, company: $company, phone: $phone) {
      id name email role status company phone
    }
  }
`;

// ── WP Sites ───────────────────────────────────────────────
export const CREATE_WP_SITE = gql`
  mutation CreateWpSite(
    $wp_client_id: String!, $name: String!, $url: String!,
    $wp_admin_url: String, $hosting_environment: String,
    $notes: String, $check_schedule: String, $priority: String,
    $auth_method: String, $auth_username: String, $auth_token: String,
    $aa_token: String
  ) {
    createWpSite(
      wp_client_id: $wp_client_id, name: $name, url: $url,
      wp_admin_url: $wp_admin_url, hosting_environment: $hosting_environment,
      notes: $notes, check_schedule: $check_schedule, priority: $priority,
      auth_method: $auth_method, auth_username: $auth_username, auth_token: $auth_token,
      aa_token: $aa_token
    ) {
      id name url wp_admin_url overall_health
      hosting_environment check_schedule priority notes
      auth_method auth_username auth_token_hint is_authenticated token_verified_at
      aa_token_hint aa_plugin_active aa_plugin_version
      wp_client { id name }
    }
  }
`;

export const DELETE_WP_SITE = gql`
  mutation DeleteWpSite($id: String!) {
    deleteWpSite(id: $id)
  }
`;

export const UPDATE_WP_SITE_AUTH = gql`
  mutation UpdateWpSiteAuth($id: String!, $auth_method: String!, $auth_username: String!, $auth_token: String!) {
    updateWpSiteAuth(id: $id, auth_method: $auth_method, auth_username: $auth_username, auth_token: $auth_token) {
      id auth_method auth_username auth_token_hint masked_token token_verified_at is_authenticated
    }
  }
`;

export const VERIFY_WP_SITE_TOKEN = gql`
  mutation VerifyWpSiteToken($id: String!) {
    verifyWpSiteToken(id: $id) {
      id auth_method auth_token_hint masked_token token_verified_at is_authenticated
    }
  }
`;

export const REMOVE_WP_SITE_AUTH = gql`
  mutation RemoveWpSiteAuth($id: String!) {
    removeWpSiteAuth(id: $id) {
      id auth_method is_authenticated
    }
  }
`;

export const UPDATE_WP_WORK_ITEM = gql`
  mutation UpdateWpWorkItem($id: String!, $status: String, $assignee_id: String, $note: String) {
    updateWpWorkItem(id: $id, status: $status, assignee_id: $assignee_id, note: $note) {
      id status notes assignee { id name }
    }
  }
`;

export const RUN_SITE_HEALTH_CHECK = gql`
  mutation RunSiteHealthCheck($id: String!) {
    runSiteHealthCheck(id: $id) {
      id status started_at
    }
  }
`;




export const MARK_NOTIFICATION_READ = gql`
  mutation MarkNotificationRead($id: String!) {
    markNotificationRead(id: $id) {
      id read_at
    }
  }
`;

// ── Developers ─────────────────────────────────────────────
export const CREATE_DEVELOPER = gql`
  mutation CreateDeveloper($name: String!, $email: String!, $password: String!, $role: String, $company: String, $bio: String) {
    createDeveloper(name: $name, email: $email, password: $password, role: $role, company: $company, bio: $bio) {
      id name email role status company bio
    }
  }
`;

export const UPDATE_DEVELOPER = gql`
  mutation UpdateDeveloper($id: String!, $name: String, $email: String, $role: String, $status: String, $company: String, $bio: String) {
    updateDeveloper(id: $id, name: $name, email: $email, role: $role, status: $status, company: $company, bio: $bio) {
      id name email role status company bio
    }
  }
`;

export const DELETE_DEVELOPER = gql`
  mutation DeleteDeveloper($id: String!) {
    deleteDeveloper(id: $id)
  }
`;

export const DELETE_CLIENT = gql`
  mutation DeleteClient($id: String!) {
    deleteClient(id: $id)
  }
`;

export const DELETE_USER = gql`
  mutation DeleteUser($id: String!) {
    deleteUser(id: $id)
  }
`;
