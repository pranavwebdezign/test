import { createContext, useContext, useState, useCallback, useEffect } from 'react';
import apolloClient from '../lib/apolloClient';
import { LOGIN, LOGOUT, REGISTER, GET_ME } from '../graphql/queries';

// ── Token helpers ──────────────────────────────────────────────────────────────
const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

function getStoredToken() {
  return localStorage.getItem(TOKEN_KEY) || null;
}
function getStoredUser() {
  try { return JSON.parse(localStorage.getItem(USER_KEY)) || null; }
  catch { return null; }
}
function storeSession(token, user) {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}
function clearSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
  apolloClient.clearStore();
}

// ── Context ────────────────────────────────────────────────────────────────────
const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(getStoredUser);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // ── Restore session on mount (re-validate token with GET_ME) ──
  useEffect(() => {
    const token = getStoredToken();
    if (!token) return;

    apolloClient
      .query({ query: GET_ME, fetchPolicy: 'network-only', context: { skipAuthRedirect: true } })
      .then(({ data }) => {
        if (data?.me) {
          setUser(data.me);
          localStorage.setItem(USER_KEY, JSON.stringify(data.me));
        } else {
          // Explicit null response — token is invalid
          clearSession();
          setUser(null);
        }
      })
      .catch((err) => {
        // Only clear session for explicit auth errors, NOT network/timeout errors.
        // On hard refresh the backend may be briefly unreachable — keep the user logged in.
        const isAuthError = err.graphQLErrors?.some((e) =>
          e.message?.toLowerCase().includes('unauthenticated') ||
          e.extensions?.code === 'UNAUTHENTICATED'
        );
        if (isAuthError) {
          clearSession();
          setUser(null);
        }
        // Network errors: silently keep stored user — they'll get re-validated on next query
      });
  }, []);

  // ── Login ──────────────────────────────────────────────────────────────────
  const login = useCallback(async ({ email, password }) => {
    setLoading(true);
    setError('');
    try {
      const { data } = await apolloClient.mutate({
        mutation: LOGIN,
        variables: { email, password },
      });
      const { token, user: apiUser } = data.login;
      storeSession(token, apiUser);
      setUser(apiUser);
      return true;
    } catch (err) {
      const msg = err.graphQLErrors?.[0]?.message
        || err.networkError?.result?.errors?.[0]?.message
        || err.message
        || 'Login failed. Check your credentials.';
      setError(msg);
      return false;
    } finally {
      setLoading(false);
    }
  }, []);

  // ── Logout ─────────────────────────────────────────────────────────────────
  const logout = useCallback(async () => {
    try {
      await apolloClient.mutate({ mutation: LOGOUT });
    } catch {
      // Ignore — clear locally regardless
    } finally {
      clearSession();
      setUser(null);
    }
  }, []);

  // ── Register ───────────────────────────────────────────────────────────────
  const signup = useCallback(async ({ name, email, company, password }) => {
    setLoading(true);
    setError('');
    try {
      const { data } = await apolloClient.mutate({
        mutation: REGISTER,
        variables: {
          name,
          email,
          password,
          password_confirmation: password,
          company: company || null,
        },
      });
      const { token, user: apiUser } = data.register;
      storeSession(token, apiUser);
      setUser(apiUser);
      return true;
    } catch (err) {
      const msg = err.graphQLErrors?.[0]?.message
        || err.message
        || 'Registration failed.';
      setError(msg);
      return false;
    } finally {
      setLoading(false);
    }
  }, []);

  // ── Permission helpers ─────────────────────────────────────────────────────
  const can = useCallback((permission) => {
    if (!user) return false;
    const perms = {
      SuperAdmin: ['manage_users', 'manage_projects', 'view_reports', 'system_settings', 'view_clients', 'view_developers', 'manage_tasks'],
      Developer: ['view_assigned_projects', 'update_tasks', 'upload_files', 'manage_tasks'],
      Client: ['view_projects', 'view_reports', 'comment_tasks'],
    };
    return perms[user.role]?.includes(permission) ?? false;
  }, [user]);

  const hasRole = useCallback((...roles) => {
    return roles.includes(user?.role);
  }, [user]);

  return (
    <AuthContext.Provider value={{ user, loading, error, login, logout, signup, can, hasRole }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside <AuthProvider>');
  return ctx;
}
