import {
  ApolloClient,
  InMemoryCache,
  createHttpLink,
  from,
} from '@apollo/client';
import { setContext } from '@apollo/client/link/context';
import { onError } from '@apollo/client/link/error';

// ── HTTP Link ───────────────────────────────────────────────
const httpLink = createHttpLink({
  uri: import.meta.env.VITE_API_URL || '/graphql',
});

// ── Auth Link (attach Bearer token from localStorage) ───────
const authLink = setContext((_, { headers }) => {
  const token = localStorage.getItem('auth_token');
  return {
    headers: {
      ...headers,
      Authorization: token ? `Bearer ${token}` : '',
      Accept: 'application/json',
    },
  };
});

// ── Error Link (401 → logout + redirect) ────────────────────
const errorLink = onError(({ graphQLErrors, networkError, operation }) => {
  // Skip redirect during the initial session check (GET_ME on mount)
  const skip = operation.getContext().skipAuthRedirect;

  if (!skip && networkError && 'statusCode' in networkError && networkError.statusCode === 401) {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    window.location.href = '/login';
  }

  if (!skip && graphQLErrors) {
    graphQLErrors.forEach(({ message }) => {
      if (message.toLowerCase().includes('unauthenticated')) {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        window.location.href = '/login';
      }
    });
  }
});

// ── Apollo Client ────────────────────────────────────────────
const apolloClient = new ApolloClient({
  link: from([errorLink, authLink, httpLink]),
  cache: new InMemoryCache({
    typePolicies: {
      WpSite: { keyFields: ['id'] },
      Project: { keyFields: ['id'] },
      Task:    { keyFields: ['id'] },
      User:    { keyFields: ['id'] },
    },
  }),
  defaultOptions: {
    watchQuery: { fetchPolicy: 'cache-and-network' },
    query:      { fetchPolicy: 'network-only' },
  },
});

export default apolloClient;
