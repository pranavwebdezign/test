import { useState, useCallback } from 'react';
import {
  Box, Typography, Button, TextField, IconButton, Chip, Divider,
  Stack, Collapse, Tooltip, Switch, FormControlLabel, Alert,
} from '@mui/material';
import {
  Add as AddIcon,
  Language as GlobeIcon,
  Delete as DeleteIcon,
  ExpandMore as ChevronDown,
} from '@mui/icons-material';

// ─── localStorage key ────────────────────────────────────────────
const STORAGE_KEY = 'ai_agent_saved_sites';

function loadSites() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
  } catch {
    return [];
  }
}

function maskToken(token) {
  if (!token || token.length <= 8) return '••••••••';
  return token.slice(0, 4) + '••••••••' + token.slice(-4);
}

function formatDate(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

// ─── Component ───────────────────────────────────────────────────
export default function SavedSitesPanel() {
  const [sites, setSites]       = useState(loadSites);
  const [showForm, setShowForm] = useState(false);
  const [domain, setDomain]     = useState('');
  const [token, setToken]       = useState('');
  const [error, setError]       = useState('');

  // Preferences (stored locally)
  const [prefs, setPrefs] = useState(() => {
    try { return JSON.parse(localStorage.getItem('ai_agent_prefs') || '{}'); } catch { return {}; }
  });

  const savePrefs = (key, value) => {
    const next = { ...prefs, [key]: value };
    setPrefs(next);
    localStorage.setItem('ai_agent_prefs', JSON.stringify(next));
  };

  const persist = useCallback((next) => {
    setSites(next);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  }, []);

  const handleAdd = () => {
    const cleanDomain = domain.trim().replace(/^https?:\/\//i, '').replace(/\/$/, '');
    const cleanToken  = token.trim();

    if (!cleanDomain) { setError('Please enter a domain.'); return; }
    if (!cleanToken)  { setError('Please enter an access token.'); return; }
    if (sites.some((s) => s.domain === cleanDomain)) {
      setError('This domain is already saved.'); return;
    }

    persist([...sites, { domain: cleanDomain, token: cleanToken, addedAt: new Date().toISOString() }]);
    setDomain('');
    setToken('');
    setError('');
    setShowForm(false);
  };

  const handleDelete = (domain) => persist(sites.filter((s) => s.domain !== domain));

  return (
    <Box>
      {/* ── Section: Saved Sites ──────────────────────────────── */}
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 1 }}>
        <Stack direction="row" spacing={1} alignItems="center">
          <GlobeIcon sx={{ color: 'success.main', fontSize: 20 }} />
          <Typography variant="subtitle1" fontWeight={700}>
            Saved Sites &amp; Tokens
          </Typography>
        </Stack>

        <Button
          size="small"
          variant="contained"
          startIcon={<AddIcon />}
          onClick={() => { setShowForm((v) => !v); setError(''); }}
          sx={{
            bgcolor: '#F5A623',
            color: '#fff',
            fontWeight: 700,
            borderRadius: 2,
            textTransform: 'none',
            '&:hover': { bgcolor: '#e0941a' },
          }}
        >
          Add Site
        </Button>
      </Box>

      <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 2 }}>
        Tokens are stored locally in your browser. Never shared.
      </Typography>

      {/* ── Add Site Form ─────────────────────────────────────── */}
      <Collapse in={showForm}>
        <Box
          sx={{
            border: '1.5px solid',
            borderColor: 'divider',
            borderRadius: 2,
            p: 2,
            mb: 2,
            bgcolor: 'background.paper',
          }}
        >
          {error && <Alert severity="error" sx={{ mb: 1.5 }}>{error}</Alert>}
          <Stack spacing={1.5}>
            <TextField
              size="small"
              placeholder="Domain (e.g. client-site.com)"
              value={domain}
              onChange={(e) => { setDomain(e.target.value); setError(''); }}
              fullWidth
              sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
            />
            <TextField
              size="small"
              placeholder="Access Token"
              value={token}
              onChange={(e) => { setToken(e.target.value); setError(''); }}
              fullWidth
              type="text"
              sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2, fontFamily: 'monospace' } }}
            />
            <Stack direction="row" spacing={1.5}>
              <Button
                variant="contained"
                onClick={handleAdd}
                sx={{
                  bgcolor: '#27AE60',
                  color: '#fff',
                  flex: 1,
                  fontWeight: 700,
                  borderRadius: 2,
                  textTransform: 'none',
                  '&:hover': { bgcolor: '#229A53' },
                }}
              >
                Save
              </Button>
              <Button
                variant="text"
                onClick={() => { setShowForm(false); setDomain(''); setToken(''); setError(''); }}
                sx={{ flex: 1, borderRadius: 2, textTransform: 'none' }}
              >
                Cancel
              </Button>
            </Stack>
          </Stack>
        </Box>
      </Collapse>

      {/* ── Saved Site List ───────────────────────────────────── */}
      <Stack spacing={1.5} sx={{ mb: 3 }}>
        {sites.length === 0 && (
          <Typography variant="body2" color="text.secondary" sx={{ py: 1 }}>
            No sites saved yet. Click <strong>Add Site</strong> to get started.
          </Typography>
        )}
        {sites.map((site) => (
          <Box
            key={site.domain}
            sx={{
              border: '1.5px solid',
              borderColor: 'divider',
              borderRadius: 2,
              px: 2,
              py: 1.5,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: 1,
              bgcolor: 'background.paper',
              transition: 'border-color 0.2s',
              '&:hover': { borderColor: 'primary.main' },
            }}
          >
            <Box sx={{ minWidth: 0 }}>
              <Stack direction="row" spacing={1} alignItems="center">
                <GlobeIcon sx={{ color: 'success.main', fontSize: 18, flexShrink: 0 }} />
                <Typography
                  variant="body2"
                  fontWeight={600}
                  noWrap
                  sx={{ color: 'primary.main' }}
                >
                  https://{site.domain}/
                </Typography>
              </Stack>
              <Typography
                variant="caption"
                color="text.secondary"
                sx={{ fontFamily: 'monospace', display: 'block', mt: 0.25 }}
              >
                {maskToken(site.token)}
                {site.addedAt && (
                  <> &nbsp;·&nbsp; Last checked {formatDate(site.addedAt)}</>
                )}
              </Typography>
            </Box>
            <Tooltip title="Remove site">
              <IconButton
                size="small"
                onClick={() => handleDelete(site.domain)}
                sx={{ color: 'text.disabled', '&:hover': { color: 'error.main' } }}
              >
                <DeleteIcon fontSize="small" />
              </IconButton>
            </Tooltip>
          </Box>
        ))}
      </Stack>

      <Divider sx={{ my: 2 }} />

      {/* ── Preferences ───────────────────────────────────────── */}
      <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 1.5 }}>
        <Box component="span" sx={{ fontSize: 18 }}>🛡️</Box>
        <Typography variant="subtitle1" fontWeight={700}>Preferences</Typography>
      </Stack>

      {[
        {
          key: 'autoScan',
          label: 'Auto-scan on page load',
          desc: 'Run guest checks automatically when you visit a site',
          default: true,
        },
        {
          key: 'badgeNotifications',
          label: 'Badge notifications',
          desc: 'Show issue count badge on the extension icon',
          default: true,
        },
        {
          key: 'darkMode',
          label: 'Dark mode',
          desc: 'Switch the dashboard to dark theme',
          default: false,
        },
      ].map(({ key, label, desc, default: def }) => (
        <Box
          key={key}
          sx={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            py: 1.25,
            borderBottom: '1px solid',
            borderColor: 'divider',
          }}
        >
          <Box>
            <Stack direction="row" spacing={1} alignItems="center">
              <GlobeIcon sx={{ fontSize: 18, color: 'text.secondary' }} />
              <Typography variant="body2" fontWeight={600}>{label}</Typography>
            </Stack>
            <Typography variant="caption" color="text.secondary" sx={{ pl: 0.5 }}>
              {desc}
            </Typography>
          </Box>
          <Switch
            checked={prefs[key] !== undefined ? prefs[key] : def}
            onChange={(e) => savePrefs(key, e.target.checked)}
            color="success"
            size="small"
          />
        </Box>
      ))}
    </Box>
  );
}
