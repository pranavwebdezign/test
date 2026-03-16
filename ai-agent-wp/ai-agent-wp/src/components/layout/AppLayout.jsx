/**
 * AppLayout — compatibility passthrough for legacy WP Ops pages.
 * When rendered inside DashboardLayout (the new SaaS shell), it simply
 * renders children so the new sidebar/navbar is used instead.
 */
export default function AppLayout({ children }) {
  return children;
}
