import { gql } from '@apollo/client';

// ── Auth ───────────────────────────────────────────────────
export const GET_ME = gql`
  query Me {
    me {
      id name email role status company phone bio avatar_url last_login_at user_prefs
    }
  }
`;

export const LOGIN = gql`
  mutation Login($email: String!, $password: String!) {
    login(email: $email, password: $password) {
      token
      user {
        id name email role status company phone bio avatar_url last_login_at
      }
    }
  }
`;

export const LOGOUT = gql`
  mutation Logout {
    logout {
      status
      message
    }
  }
`;

export const REGISTER = gql`
  mutation Register($name: String!, $email: String!, $password: String!, $password_confirmation: String!, $company: String) {
    register(name: $name, email: $email, password: $password, password_confirmation: $password_confirmation, company: $company) {
      token
      user {
        id name email role status company
      }
    }
  }
`;

// ── SaaS ───────────────────────────────────────────────────
export const GET_DASHBOARD_STATS = gql`
  query DashboardStats {
    dashboardStats {
      total_projects active_projects completed_projects
      total_clients active_clients
      open_tasks done_tasks total_revenue monthly_revenue
    }
  }
`;

export const GET_PROJECTS = gql`
  query Projects($status: String, $client_id: String, $search: String) {
    projects(status: $status, client_id: $client_id, search: $search) {
      id name description status progress budget due_date
      is_wordpress wp_site_url wp_site_id
      tasks_count open_tasks_count
      client { id name company }
      developers { id name email avatar_url }
    }
  }
`;

export const GET_PROJECT = gql`
  query Project($id: String!) {
    project(id: $id) {
      id name description status progress budget due_date started_at completed_at
      is_wordpress wp_site_url wp_site_id
      client { id name company email }
      developers { id name email avatar_url }
      tasks { id title status priority due_date assignee { id name } }
    }
  }
`;

export const GET_TASKS = gql`
  query Tasks($project_id: String, $assignee_id: String, $status: String, $priority: String) {
    tasks(project_id: $project_id, assignee_id: $assignee_id, status: $status, priority: $priority) {
      id title description status priority due_date completed_at sort_order
      project { id name }
      assignee { id name avatar_url }
      comments { id content created_at user { id name avatar_url } }
    }
  }
`;

export const GET_TASK = gql`
  query Task($id: String!) {
    task(id: $id) {
      id title description status priority due_date completed_at sort_order
      project { id name }
      assignee { id name avatar_url }
      comments { id content created_at user { id name avatar_url } }
    }
  }
`;

export const GET_CLIENTS = gql`
  query Clients($status: String, $search: String) {
    clients(status: $status, search: $search) {
      id name email company phone status revenue joined_at
    }
  }
`;

export const GET_CLIENT = gql`
  query Client($id: String!) {
    client(id: $id) {
      id name email company phone address status revenue joined_at notes
      projects {
        id name status progress budget due_date
        developers { id name }
      }
    }
  }
`;

export const GET_USERS = gql`
  query Users($role: String, $status: String, $search: String) {
    users(role: $role, status: $status, search: $search) {
      id name email role status company phone last_login_at
    }
  }
`;

export const GET_DEVELOPERS = gql`
  query Developers($status: String) {
    developers(status: $status) {
      id name email role status company bio
    }
  }
`;

export const GET_DEVELOPER = gql`
  query Developer($id: String!) {
    developer(id: $id) {
      id name email role status company bio avatar_url
      tasks { id title status priority due_date project { id name } }
      projects { id name status progress due_date }
    }
  }
`;

// ── WP Ops ─────────────────────────────────────────────────
export const GET_WP_DASHBOARD = gql`
  query WpDashboard {
    wpDashboard {
      total_sites healthy_sites warning_sites critical_sites unknown_sites
      open_work_items pending_updates
    }
  }
`;

export const GET_WP_SITES = gql`
  query WpSites($health: String, $wp_client_id: String, $search: String) {
    wpSites(health: $health, wp_client_id: $wp_client_id, search: $search) {
      id name url domain overall_health php_version wp_version
      active_plugins plugin_updates theme_updates core_update_available
      is_up wp_admin_accessible last_checked_at check_schedule priority
      auth_method auth_username auth_token_hint masked_token token_verified_at is_authenticated
      aa_plugin_active aa_plugin_version aa_token_hint is_aa_configured
      pending_updates_count
      wp_client { id name }
    }
  }
`;

export const GET_WP_SITE = gql`
  query WpSite($id: String!) {
    wpSite(id: $id) {
      id name url domain wp_admin_url hosting_environment overall_health
      php_version wp_version active_plugins plugin_updates theme_updates
      core_update_available is_up wp_admin_accessible last_checked_at
      auth_method auth_username auth_token_hint masked_token token_verified_at is_authenticated
      aa_plugin_active aa_plugin_version aa_token_hint aa_token_verified_at is_aa_configured
      auto_scan_enabled badge_notifications notes check_schedule priority pending_updates_count
      wp_client { id name }
      findings { id check_type title description severity detected_at resolved_at }
      work_items { id title description severity status notes created_at updated_at resolved_at assignee { id name } }
      check_history { id status findings_count error_message started_at completed_at duration }
      security_issues { id type title description severity detected_at resolved_at }
    }
  }
`;

export const GET_WP_WORK_ITEMS = gql`
  query WpWorkItems($site_id: String, $status: String, $severity: String, $assignee_id: String) {
    wpWorkItems(site_id: $site_id, status: $status, severity: $severity, assignee_id: $assignee_id) {
      id title description severity status notes created_at updated_at resolved_at
      site { id name url overall_health wp_client { id name } }
      assignee { id name avatar_url }
      finding { id check_type }
    }
  }
`;

export const GET_WP_FINDINGS = gql`
  query WpFindings($site_id: String, $severity: String, $check_type: String) {
    wpFindings(site_id: $site_id, severity: $severity, check_type: $check_type) {
      id check_type title description severity raw_data detected_at resolved_at
      site { id name url wp_client { id name } }
    }
  }
`;

export const GET_WP_CHECK_HISTORY = gql`
  query WpCheckHistory($site_id: String, $limit: Int) {
    wpCheckHistory(site_id: $site_id, limit: $limit) {
      id status findings_count error_message started_at completed_at duration
      site { id name url wp_client { id name } }
    }
  }
`;

export const GET_WP_CLIENTS = gql`
  query WpClients {
    wpClients {
      id name slug email sites_count created_at
    }
  }
`;

// ── New: Lighthouse, SEO, Google Services, Portal Settings ─────────────────

export const GET_SITE_LIGHTHOUSE = gql`
  query GetSiteLighthouse($site_id: String!, $device: String) {
    getSiteLighthouse(site_id: $site_id, device: $device) {
      id url device
      performance accessibility best_practices seo_score
      fcp lcp cls speed_index
      issues_count performance_issues accessibility_issues seo_issues
      source scanned_at
    }
  }
`;


export const GET_SITE_SEO = gql`
  query GetSiteSeo($site_id: String!) {
    getSiteSeo(site_id: $site_id) {
      id url overall_score
      has_title title_length has_meta_desc meta_desc_length
      h1_count images_total images_no_alt images_alt_percent
      has_canonical has_og_tags has_structured_data
      links_internal links_external links_no_text
      word_count issues_json scanned_at
    }
  }
`;

export const GET_SITE_GOOGLE_SERVICES = gql`
  query GetSiteGoogleServices($site_id: String!) {
    getSiteGoogleServices(site_id: $site_id) {
      id has_ga4 ga4_ids has_gtm gtm_ids
      has_google_ads has_recaptcha recaptcha_versions
      has_maps has_fonts has_search_console
      total_services scanned_at
    }
  }
`;

export const PORTAL_SETTINGS_QUERY = gql`
  query PortalSettings($group: String) {
    portalSettings(group: $group) {
      key group description is_encrypted is_configured hint value updated_at
    }
  }
`;

// ── New mutations ───────────────────────────────────────────────────────────

export const UPDATE_PORTAL_SETTING = gql`
  mutation UpdatePortalSetting($key: String!, $value: String!) {
    updatePortalSetting(key: $key, value: $value) {
      key is_configured hint updated_at
    }
  }
`;

export const TEST_WORDFENCE_KEY = gql`
  mutation TestWordfenceKey($api_key: String) {
    testWordfenceKey(api_key: $api_key)
  }
`;

export const TEST_LIGHTHOUSE_KEY = gql`
  mutation TestLighthouseKey($api_key: String) {
    testLighthouseKey(api_key: $api_key)
  }
`;

export const UPDATE_WP_SITE_AA_TOKEN = gql`
  mutation UpdateWpSiteAaToken($id: String!, $aa_token: String!) {
    updateWpSiteAaToken(id: $id, aa_token: $aa_token) {
      id aa_plugin_active aa_plugin_version aa_token_hint aa_token_verified_at is_aa_configured
    }
  }
`;

export const VERIFY_WP_SITE_AA_TOKEN = gql`
  mutation VerifyWpSiteAaToken($id: String!) {
    verifyWpSiteAaToken(id: $id) {
      id aa_plugin_active aa_plugin_version aa_token_hint aa_token_verified_at is_aa_configured
    }
  }
`;

export const REFRESH_SITE_DATA = gql`
  mutation RefreshSiteData($site_id: String!, $data_type: String) {
    refreshSiteData(site_id: $site_id, data_type: $data_type) {
      id status findings_count started_at
    }
  }
`;

// ── Site Authentication mutations ───────────────────────────────────────────

export const UPDATE_WP_SITE_AUTH = gql`
  mutation UpdateWpSiteAuth(
    $id: String!
    $auth_method: String!
    $auth_username: String!
    $auth_token: String!
  ) {
    updateWpSiteAuth(
      id: $id
      auth_method: $auth_method
      auth_username: $auth_username
      auth_token: $auth_token
    ) {
      id auth_method auth_username auth_token_hint masked_token
      token_verified_at is_authenticated
    }
  }
`;

export const VERIFY_WP_SITE_TOKEN = gql`
  mutation VerifyWpSiteToken($id: String!) {
    verifyWpSiteToken(id: $id) {
      id auth_method auth_username auth_token_hint masked_token
      token_verified_at is_authenticated
    }
  }
`;

export const REMOVE_WP_SITE_AUTH = gql`
  mutation RemoveWpSiteAuth($id: String!) {
    removeWpSiteAuth(id: $id) {
      id auth_method auth_username auth_token_hint token_verified_at is_authenticated
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

export const UPDATE_WP_SITE = gql`
  mutation UpdateWpSite(
    $id: String!
    $name: String
    $url: String
    $notes: String
    $hosting_environment: String
    $priority: String
    $check_schedule: String
    $auth_method: String
    $auth_username: String
    $auth_token: String
    $wp_client_id: String
  ) {
    updateWpSite(
      id: $id
      name: $name
      url: $url
      notes: $notes
      hosting_environment: $hosting_environment
      priority: $priority
      check_schedule: $check_schedule
      auth_method: $auth_method
      auth_username: $auth_username
      auth_token: $auth_token
      wp_client_id: $wp_client_id
    ) {
      id name url domain notes hosting_environment priority check_schedule
      auth_method auth_username auth_token_hint masked_token is_authenticated
      wp_client { id name }
    }
  }
`;

// ── Update Management mutations ─────────────────────────────────────────────

export const TRIGGER_SITE_UPDATE = gql`
  mutation TriggerSiteUpdate($id: String!, $type: String!, $item_slug: String) {
    triggerSiteUpdate(id: $id, type: $type, item_slug: $item_slug) {
      id plugin_updates theme_updates core_update_available
    }
  }
`;

export const UPDATE_WP_WORK_ITEM = gql`
  mutation UpdateWpWorkItem($id: String!, $status: String, $notes: String, $assignee_id: String) {
    updateWpWorkItem(id: $id, status: $status, notes: $notes, assignee_id: $assignee_id) {
      id title status severity notes updated_at
      assignee { id name }
    }
  }
`;

export const GET_NOTIFICATIONS = gql`
  query Notifications($unread_only: Boolean) {
    notifications(unread_only: $unread_only) {
      id type message related_type related_id read_at created_at
    }
  }
`;

// ── WP Check History ─────────────────────────────────────────────────────────

export const GET_SITE_LATEST_CHECK = gql`
  query GetSiteLatestCheck($site_id: String!) {
    wpCheckHistory(site_id: $site_id, limit: 1) {
      id status data_type started_at completed_at
      health_score findings_count
      plugins_needing_update
      themes_needing_update
      all_plugins
      all_themes
      wp_core_update_version
    }
  }
`;



