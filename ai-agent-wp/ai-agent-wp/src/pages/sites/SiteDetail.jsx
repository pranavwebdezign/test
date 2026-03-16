import { useState } from 'react';
import { useParams, useNavigate, Link as RouterLink } from 'react-router-dom';
import { useQuery, useMutation } from '@apollo/client';
import {
  Box, Grid, Card, CardContent, Typography, Button, Chip, Stack, Tabs, Tab,
  Table, TableBody, TableCell, TableContainer, TableHead, TableRow,
  LinearProgress, IconButton, Tooltip, Divider, Badge, CircularProgress,
  Link as MuiLink, ToggleButton, ToggleButtonGroup, TextField, InputAdornment,
  Alert, Skeleton, Select, MenuItem, FormControl, InputLabel, Avatar,
} from '@mui/material';
import {
  Edit, Refresh, ArrowBack, CheckCircle, Warning, Error as ErrorIcon,
  HelpOutline, Code, Language, AccessTime, Link as LinkIcon,
  Security as SecurityIcon, History as HistoryIcon, List as ListIcon,
  GridView, Sync, BugReport, Update, Speed, Analytics, Extension,
  VisibilityOff, Visibility, Person, CheckCircleOutline, Cancel, Save,
  Key, VerifiedUser, LinkOff, PlayArrow, Map, TextFields, Search as SearchIcon,
} from '@mui/icons-material';
import { formatDistanceToNow, format } from 'date-fns';
import AppLayout from '../../components/layout/AppLayout';
import EmptyState from '../../components/common/EmptyState';
import { useConfirmation } from '../../context/ConfirmationContext';
import { useNotification } from '../../hooks/useNotification';
import AppSnackbar from '../../components/AppSnackbar';
import {
  GET_WP_SITE, GET_SITE_LIGHTHOUSE, GET_SITE_SEO, GET_SITE_GOOGLE_SERVICES,
  UPDATE_WP_SITE_AA_TOKEN, VERIFY_WP_SITE_AA_TOKEN,
  UPDATE_WP_SITE_AUTH, VERIFY_WP_SITE_TOKEN, REMOVE_WP_SITE_AUTH,
  RUN_SITE_HEALTH_CHECK, UPDATE_WP_SITE,
  GET_SITE_LATEST_CHECK, TRIGGER_SITE_UPDATE,
} from '../../graphql/queries';


// ── Helpers ───────────────────────────────────────────────────────────────────
const fmtDatetime = (val) => {
  if (!val) return '—';
  try { return format(new Date(val), 'MMM d, yyyy HH:mm'); } catch { return val; }
};

// ── Health badge ──────────────────────────────────────────────────────────────
const healthMeta = {
  healthy: { label: 'Healthy', color: '#16A34A', bg: '#DCFCE7', icon: CheckCircle },
  warning: { label: 'Warning', color: '#D97706', bg: '#FEF9C3', icon: Warning },
  critical: { label: 'Critical', color: '#DC2626', bg: '#FEE2E2', icon: ErrorIcon },
  unknown: { label: 'Unknown', color: '#5C4A8A', bg: '#EDE8FC', icon: HelpOutline },
};

function HealthBadge({ status, large }) {
  const m = healthMeta[status] ?? healthMeta.unknown;
  const Icon = m.icon;
  if (large) {
    return (
      <Box>
        <Typography variant="caption" color="text.secondary" gutterBottom>Health Status</Typography>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: 0.5 }}>
          <Icon sx={{ color: m.color, fontSize: 20 }} />
          <Typography variant="h5" fontWeight={700} sx={{ color: m.color }}>{m.label}</Typography>
        </Box>
      </Box>
    );
  }
  return (
    <Chip label={m.label} size="small"
      sx={{
        bgcolor: m.bg, color: m.color, fontWeight: 700, fontSize: '0.78rem',
        border: `1px solid ${m.color}40`, '& .MuiChip-label': { px: 1.2 }
      }} />
  );
}

// ── Status cards row ──────────────────────────────────────────────────────────
function StatusCard({ children, sx }) {
  return (
    <Card sx={{ borderRadius: 2, border: '1px solid #DDD4F8', boxShadow: 'none', flex: 1, minWidth: 0, ...sx }}>
      <CardContent sx={{ p: 2, '&:last-child': { pb: 2 } }}>{children}</CardContent>
    </Card>
  );
}

// ── Tab panel wrapper ─────────────────────────────────────────────────────────
function TabPanel({ value, index, children }) {
  return value === index ? <Box sx={{ pt: 3 }}>{children}</Box> : null;
}

// ── Overview: Site Info Grid ──────────────────────────────────────────────────
function SiteInfoGrid({ site, coreUpdateVersion, onUpdateCore, updatingCore }) {
  const rows = [
    { label: 'Site URL', value: <MuiLink href={site.url} target="_blank" sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}><LinkIcon sx={{ fontSize: 14 }} />{site.url}</MuiLink> },
    { label: 'WP Admin', value: <MuiLink href={site.wp_admin_url} target="_blank" sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}><LinkIcon sx={{ fontSize: 14 }} />{site.wp_admin_url}</MuiLink> },
    { label: 'Client', value: site.wp_client?.name || '—' },
    { label: 'Hosting', value: <Typography variant="body2" color="primary.main" fontWeight={500}>{site.hosting_environment}</Typography> },
    { label: 'PHP Version', value: <Chip label={site.php_version ?? '—'} size="small" sx={{ bgcolor: '#FEF9C3', color: '#854D0E', fontWeight: 700, height: 22 }} /> },
    {
      label: 'WordPress Version',
      value: (
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap' }}>
          <Chip label={site.wp_version ?? '—'} size="small" sx={{ bgcolor: '#DCFCE7', color: '#15803D', fontWeight: 700, height: 22 }} />
          {coreUpdateVersion && (
            <Chip label={`↗ ${coreUpdateVersion} available`} size="small"
              sx={{ bgcolor: '#FEF3C7', color: '#92400E', fontWeight: 600, height: 22, fontSize: '0.7rem' }} />
          )}
          {coreUpdateVersion && onUpdateCore && (
            <Button
              size="small" variant="contained"
              disabled={updatingCore}
              startIcon={updatingCore ? <CircularProgress size={11} color="inherit" /> : <Update sx={{ fontSize: 13 }} />}
              onClick={onUpdateCore}
              sx={{ bgcolor: '#8E43F0', '&:hover': { bgcolor: '#7330D4' }, borderRadius: 1.5, fontSize: '0.72rem', py: 0.3, px: 1.2, height: 24 }}
            >
              {updatingCore ? 'Updating…' : 'Update WordPress'}
            </Button>
          )}
        </Box>
      )
    },
    { label: 'Last Checked', value: site.last_checked_at ? fmtDatetime(site.last_checked_at) : '—' },
    { label: 'Site Status', value: site.is_up ? <Chip label="Online" size="small" icon={<CheckCircle sx={{ fontSize: '14px!important' }} />} sx={{ bgcolor: '#DCFCE7', color: '#15803D', fontWeight: 600, height: 22 }} /> : <Chip label="Offline" size="small" sx={{ bgcolor: '#FEE2E2', color: '#DC2626', fontWeight: 600, height: 22 }} /> },
  ];

  return (
    <TableContainer>
      <Table size="small">
        <TableBody>
          {rows.map((row) => (
            <TableRow key={row.label} sx={{ '&:last-child td': { border: 0 } }}>
              <TableCell sx={{ color: 'text.secondary', fontWeight: 500, whiteSpace: 'nowrap', width: 160, border: 'none', py: 1, pl: 0 }}>
                {row.label}
              </TableCell>
              <TableCell sx={{ border: 'none', py: 1 }}>
                {typeof row.value === 'string'
                  ? <Typography variant="body2">{row.value}</Typography>
                  : row.value}
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </TableContainer>
  );
}

// ── Right sidebar panel ───────────────────────────────────────────────────────
function SiteRightPanel({ site, plugins, notify }) {
  const activeCount = plugins.filter((p) => p.active).length || (site.active_plugins ?? 0);
  const updateCount = plugins.filter((p) => p.updateAvailable).length;
  const securityCount = (site?.security_issues ?? []).length;
  const totalPlugins = site.active_plugins || plugins.length;

  return (
    <Stack spacing={2}>
      {/* Active Plugins */}
      <Card sx={{ borderRadius: 2, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
        <CardContent sx={{ p: 2.5 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1.5 }}>
            <GridView sx={{ fontSize: 16, color: 'text.secondary' }} />
            <Typography variant="body2" fontWeight={600} color="text.secondary">Active Plugins</Typography>
          </Box>
          <Typography variant="h5" fontWeight={700} sx={{ mb: 1 }}>
            <Box component="span" sx={{ color: '#8E43F0' }}>{activeCount}</Box>
            <Box component="span" color="text.secondary"> / {totalPlugins}</Box>
          </Typography>
          <LinearProgress
            variant="determinate"
            value={totalPlugins ? (activeCount / totalPlugins) * 100 : 0}
            sx={{ borderRadius: 4, height: 8, bgcolor: '#DDD4F8', '& .MuiLinearProgress-bar': { bgcolor: '#8E43F0' } }}
          />
        </CardContent>
      </Card>

      {/* Pending Updates */}
      <Card sx={{ borderRadius: 2, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
        <CardContent sx={{ p: 2.5 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 1.5 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
              <Sync sx={{ fontSize: 16, color: 'text.secondary' }} />
              <Typography variant="body2" fontWeight={600} color="text.secondary">Pending Updates</Typography>
            </Box>
          </Box>
          <Typography variant="h5" fontWeight={700} sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1.5 }}>
            <Sync sx={{ fontSize: 20, color: '#D97706' }} />
            {(site.plugin_updates || 0) + (site.theme_updates || 0) + (site.core_update_available ? 1 : 0)}
          </Typography>
          <Stack direction="row" spacing={1} flexWrap="wrap" gap={1}>
            {site.theme_updates > 0 && (
              <Chip label="Theme" size="small" sx={{ bgcolor: '#FEF3C7', color: '#92400E', fontWeight: 600, height: 22, fontSize: '0.72rem' }} />
            )}
            {site.plugin_updates > 0 && (
              <Chip label={`${site.plugin_updates} Plugins`} size="small" sx={{ bgcolor: '#EDE8FC', color: '#6A1FCC', fontWeight: 600, height: 22, fontSize: '0.72rem' }} />
            )}
            {site.core_update_available && (
              <Chip label="WP Core" size="small" sx={{ bgcolor: '#FCE7F3', color: '#9D174D', fontWeight: 600, height: 22, fontSize: '0.72rem' }} />
            )}
            {((site.plugin_updates || 0) + (site.theme_updates || 0) + (site.core_update_available ? 1 : 0)) === 0 && (
              <Typography variant="caption" color="success.main" fontWeight={600}>All up to date ✓</Typography>
            )}
          </Stack>
        </CardContent>
      </Card>

      {/* Security Issues */}
      <Card sx={{ borderRadius: 2, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
        <CardContent sx={{ p: 2.5 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1.5 }}>
            <SecurityIcon sx={{ fontSize: 16, color: 'text.secondary' }} />
            <Typography variant="body2" fontWeight={600} color="text.secondary">Security Issues</Typography>
          </Box>
          {securityCount === 0
            ? <Typography variant="body2" color="success.main" fontWeight={600}>No issues found ✓</Typography>
            : (
              <Typography variant="h5" fontWeight={700} sx={{ color: '#DC2626' }}>
                {securityCount} <Box component="span" sx={{ fontSize: '0.85rem', fontWeight: 500, color: 'text.secondary' }}>issue{securityCount > 1 ? 's' : ''} found</Box>
              </Typography>
            )}
        </CardContent>
      </Card>

      {/* ── Wordfence / Critical Health Brief ── */}
      {(site.overall_health === 'critical' || site.overall_health === 'warning') && (
        <WorkItemsBriefPanel site={site} />
      )}

      {/* ── Site Connection (AA Plugin / WP Admin) ── */}
      <SiteConnectionPanel site={site} notify={notify} />

      {/* ── Lighthouse ── */}
      <LighthouseCard siteId={site.id} />

      {/* ── Google Services ── */}
      <GoogleServicesCard siteId={site.id} />

      {/* ── Schedule Picker ── */}
      <ScheduleCard site={site} notify={notify} />
    </Stack>
  );
}

// ── Wordfence / Critical Health Brief ──────────────────────────────────────────
function WorkItemsBriefPanel({ site }) {
  const openItems = (site.work_items ?? []).filter(w => w.status === 'open');
  const p0Items = openItems.filter(w => w.severity === 'P0');
  const p1Items = openItems.filter(w => w.severity === 'P1');
  const criticalItems = [...p0Items, ...p1Items].slice(0, 3);
  if (criticalItems.length === 0) return null;

  const bgColor = site.overall_health === 'critical' ? '#FEF2F2' : '#FFFBEB';
  const borderColor = site.overall_health === 'critical' ? '#FECACA' : '#FDE68A';
  const titleColor = site.overall_health === 'critical' ? '#DC2626' : '#D97706';

  return (
    <Card sx={{ borderRadius: 2, border: `1px solid ${borderColor}`, boxShadow: 'none', bgcolor: bgColor }}>
      <CardContent sx={{ p: 2.5 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1.5 }}>
          <BugReport sx={{ fontSize: 16, color: titleColor }} />
          <Typography variant="body2" fontWeight={700} color={titleColor} sx={{ textTransform: 'uppercase', letterSpacing: '0.06em', fontSize: '0.68rem' }}>
            {site.overall_health === 'critical' ? 'Why This Site Is Critical' : 'Active Warnings'}
          </Typography>
        </Box>
        <Stack spacing={1}>
          {criticalItems.map((item) => (
            <Box key={item.id} sx={{ display: 'flex', alignItems: 'flex-start', gap: 1 }}>
              <Chip
                label={item.severity}
                size="small"
                sx={{
                  height: 18, fontSize: '0.6rem', fontWeight: 700, flexShrink: 0,
                  bgcolor: item.severity === 'P0' ? '#FEE2E2' : '#FEF3C7',
                  color: item.severity === 'P0' ? '#DC2626' : '#92400E',
                }}
              />
              <Typography variant="caption" fontWeight={600} sx={{ lineHeight: 1.4 }}>{item.title}</Typography>
            </Box>
          ))}
        </Stack>
        {openItems.length > criticalItems.length && (
          <Typography variant="caption" color="text.disabled" sx={{ mt: 1, display: 'block' }}>
            +{openItems.length - criticalItems.length} more open item{openItems.length - criticalItems.length > 1 ? 's' : ''}
          </Typography>
        )}
      </CardContent>
    </Card>
  );
}

// ── Schedule Picker Card ───────────────────────────────────────────────────────
const SCHEDULE_LABELS = {
  hourly:   'Every Hour',
  '6h':     'Every 6 Hours',
  every_6h: 'Every 6 Hours',
  '12h':    'Every 12 Hours',
  daily:    'Daily',
  weekly:   'Weekly',
  manual:   'Manual Only',
};

function ScheduleCard({ site, notify }) {
  const [updateSite, { loading }] = useMutation(UPDATE_WP_SITE, {
    onCompleted: () => notify('Check schedule updated.', 'success'),
    onError: (e) => notify(e.message, 'error'),
    refetchQueries: [{ query: GET_WP_SITE, variables: { id: site.id } }],
  });

  const hasAuth = site.is_authenticated || site.aa_plugin_active;

  const handleChange = (e) => {
    updateSite({ variables: { id: site.id, check_schedule: e.target.value } });
  };

  return (
    <Card sx={{ borderRadius: 2, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
      <CardContent sx={{ p: 2.5 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1.5 }}>
          <AccessTime sx={{ fontSize: 16, color: 'text.secondary' }} />
          <Typography variant="body2" fontWeight={600} color="text.secondary"
            sx={{ textTransform: 'uppercase', letterSpacing: '0.08em', fontSize: '0.68rem' }}
          >
            Check Schedule
          </Typography>
          {loading && <CircularProgress size={12} sx={{ ml: 'auto' }} />}
        </Box>

        <FormControl fullWidth size="small" disabled={loading}>
          <Select
            value={site.check_schedule ?? 'daily'}
            onChange={handleChange}
            displayEmpty
            sx={{ fontSize: '0.85rem' }}
          >
            {Object.entries(SCHEDULE_LABELS).map(([v, label]) => (
              <MenuItem key={v} value={v} sx={{ fontSize: '0.85rem' }}>{label}</MenuItem>
            ))}
          </Select>
        </FormControl>

        {!hasAuth && (
          <Typography variant="caption" color="warning.main" sx={{ mt: 1, display: 'block' }}>
            Connect this site first to enable automatic checks.
          </Typography>
        )}

        {site.last_checked_at && (
          <Typography variant="caption" color="text.secondary" sx={{ mt: 0.5, display: 'block' }}>
            Last checked {formatDistanceToNow(new Date(site.last_checked_at), { addSuffix: true })}
          </Typography>
        )}
      </CardContent>
    </Card>
  );
}

// ── Lighthouse sidebar mini-card ─────────────────────────────────────────────

function scoreColor(s) {
  if (s >= 90) return '#16A34A';
  if (s >= 70) return '#D97706';
  return '#DC2626';
}

function LighthouseCard({ siteId }) {
  const [device, setDevice] = useState('mobile');
  const { data: lhData } = useQuery(GET_SITE_LIGHTHOUSE, {
    variables: { site_id: siteId, device },
    skip: !siteId,
  });
  const lh = lhData?.getSiteLighthouse;
  const scores = [
    { label: 'Performance', score: lh?.performance ?? 0 },
    { label: 'Accessibility', score: lh?.accessibility ?? 0 },
    { label: 'Best Practices', score: lh?.best_practices ?? 0 },
    { label: 'SEO', score: lh?.seo_score ?? 0 },
  ];
  return (
    <Card sx={{ borderRadius: 2, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
      <CardContent sx={{ p: 2 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 1.5 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Speed sx={{ fontSize: 16, color: 'text.secondary' }} />
            <Typography variant="caption" fontWeight={700} color="text.secondary" sx={{ textTransform: 'uppercase', letterSpacing: '0.08em' }}>
              Lighthouse
            </Typography>
          </Box>
          <ToggleButtonGroup
            value={device} exclusive size="small"
            onChange={(_, v) => v && setDevice(v)}
            sx={{ '& .MuiToggleButton-root': { px: 1, py: 0.2, fontSize: '0.6rem', fontWeight: 700, borderRadius: '6px!important', border: '1px solid #DDD4F8' } }}
          >
            <ToggleButton value="mobile">📱</ToggleButton>
            <ToggleButton value="desktop">🖥</ToggleButton>
          </ToggleButtonGroup>
        </Box>
        <Box sx={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 1 }}>
          {scores.map((s) => (
            <Box key={s.label} sx={{ border: '1px solid #DDD4F8', borderRadius: 1.5, p: 1.2, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <Typography variant="caption" color="text.secondary" fontWeight={500} sx={{ lineHeight: 1.2, fontSize: '0.68rem' }}>{s.label}</Typography>
              <Typography variant="body2" fontWeight={800} sx={{ color: scoreColor(s.score), minWidth: 28, textAlign: 'right' }}>{s.score || '—'}</Typography>
            </Box>
          ))}
        </Box>
        {lh?.scanned_at && (
          <Typography variant="caption" color="text.disabled" sx={{ mt: 1, display: 'block', fontSize: '0.65rem' }}>
            {device === 'mobile' ? '📱' : '🖥'} Scanned {fmtDatetime(lh.scanned_at)}
          </Typography>
        )}
      </CardContent>
    </Card>
  );
}

// ── Lighthouse Full Tab — real Apollo data ───────────────────────────────────
function ScoreDial({ label, score }) {
  const color = score >= 90 ? '#16A34A' : score >= 70 ? '#D97706' : score >= 50 ? '#F59E0B' : '#DC2626';
  const bg = score >= 90 ? '#DCFCE7' : score >= 70 ? '#FEF9C3' : score >= 50 ? '#FEF3C7' : '#FEE2E2';
  return (
    <Box sx={{ textAlign: 'center', flex: 1 }}>
      <Box sx={{
        width: 72, height: 72, borderRadius: '50%', border: `6px solid ${color}`,
        bgcolor: bg, display: 'flex', alignItems: 'center', justifyContent: 'center',
        mx: 'auto', mb: 1,
      }}>
        <Typography fontWeight={800} fontSize={20} color={color}>{score}</Typography>
      </Box>
      <Typography variant="caption" fontWeight={600} color="text.secondary" sx={{ fontSize: '0.7rem' }}>{label}</Typography>
    </Box>
  );
}

function LighthouseTab({ siteId }) {
  const [device, setDevice] = useState('mobile');
  const { data, loading } = useQuery(GET_SITE_LIGHTHOUSE, {
    variables: { site_id: siteId, device },
    skip: !siteId,
  });
  const lh = data?.getSiteLighthouse;

  if (loading) {
    return (
      <Stack spacing={2.5}>
        <Skeleton variant="rounded" height={220} />
        <Skeleton variant="rounded" height={140} />
      </Stack>
    );
  }

  if (!lh) {
    return (
      <Alert severity="info" sx={{ borderRadius: 2 }}>
        No Lighthouse data yet. Add Google PageSpeed API key in <strong>Portal Settings → API Integrations</strong>, then run a health check.
      </Alert>
    );
  }

  const vitals = [
    ['FCP — First Contentful Paint', lh.fcp],
    ['LCP — Largest Contentful Paint', lh.lcp],
    ['CLS — Cumulative Layout Shift', lh.cls],
    ['Speed Index', lh.speed_index],
  ];

  // GraphQL returns these as JSON strings — parse them safely
  const parseIssues = (raw) => {
    if (!raw) return [];
    if (Array.isArray(raw)) return raw;
    try { const p = JSON.parse(raw); return Array.isArray(p) ? p : []; } catch { return []; }
  };
  const issuesList = [
    ...parseIssues(lh.performance_issues),
    ...parseIssues(lh.accessibility_issues),
    ...parseIssues(lh.seo_issues),
  ];

  return (
    <Stack spacing={2.5}>
      {/* Device Toggle Header */}
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <Typography variant="h6" fontWeight={700}>Lighthouse Scores</Typography>
        <ToggleButtonGroup
          value={device} exclusive size="small"
          onChange={(_, v) => v && setDevice(v)}
          sx={{
            '& .MuiToggleButton-root': {
              px: 2, py: 0.5, fontWeight: 700, fontSize: '0.78rem',
              borderColor: '#DDD4F8',
              '&.Mui-selected': { bgcolor: '#8E43F0', color: '#fff', borderColor: '#8E43F0' },
            },
          }}
        >
          <ToggleButton value="mobile">📱 Mobile</ToggleButton>
          <ToggleButton value="desktop">🖥 Desktop</ToggleButton>
        </ToggleButtonGroup>
      </Box>

      <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
        <CardContent sx={{ p: 3 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 3 }}>
            <Typography variant="body1" fontWeight={600} color="text.secondary">
              {device === 'mobile' ? '📱 Mobile results' : '🖥 Desktop results'} — matches Google PageSpeed Insights
            </Typography>
            <Chip
              label={lh.source === 'google_api' ? 'Google PageSpeed API' : lh.source === 'plugin_cache' ? 'Plugin Cache' : 'Sample Data'}
              size="small"
              sx={{ bgcolor: lh.source === 'google_api' ? '#DCFCE7' : '#EDE8FC', color: lh.source === 'google_api' ? '#15803D' : '#5C4A8A', fontWeight: 600, height: 22 }}
            />
          </Box>
          <Box sx={{ display: 'flex', gap: 2, mb: 3 }}>
            <ScoreDial label="Performance" score={lh.performance} />
            <ScoreDial label="Accessibility" score={lh.accessibility} />
            <ScoreDial label="Best Practices" score={lh.best_practices} />
            <ScoreDial label="SEO" score={lh.seo_score} />
          </Box>
          <Divider sx={{ mb: 2.5 }} />
          <Typography variant="body2" fontWeight={700} sx={{ mb: 1.5 }}>Core Web Vitals</Typography>
          <Box sx={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 1.5 }}>
            {vitals.filter(([, val]) => val).map(([label, val]) => (
              <Box key={label} sx={{ p: 1.5, bgcolor: '#F7F5FF', borderRadius: 1.5, border: '1px solid #DDD4F8' }}>
                <Typography variant="caption" color="text.secondary" sx={{ fontSize: '0.7rem', display: 'block' }}>{label}</Typography>
                <Typography variant="body1" fontWeight={700} sx={{ fontFamily: 'monospace' }}>{val}</Typography>
              </Box>
            ))}
          </Box>
          {lh.scanned_at && (
            <Typography variant="caption" color="text.disabled" sx={{ mt: 1.5, display: 'block' }}>
              Last scanned: {fmtDatetime(lh.scanned_at)}
            </Typography>
          )}
        </CardContent>
      </Card>
      {issuesList.length > 0 && (
        <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
          <CardContent sx={{ p: 3 }}>
            <Typography variant="h6" fontWeight={700} sx={{ mb: 2 }}>Opportunities</Typography>
            <Stack spacing={1}>
              {issuesList.map((issue, i) => (
                <Box key={i} sx={{ display: 'flex', alignItems: 'flex-start', gap: 1, p: 1.5, bgcolor: '#FEF9C3', borderRadius: 1.5, border: '1px solid #FDE68A' }}>
                  <Warning sx={{ fontSize: 16, color: '#D97706', flexShrink: 0, mt: 0.2 }} />
                  <Box sx={{ flex: 1 }}>
                    <Typography variant="body2" fontWeight={600}>{typeof issue === 'string' ? issue : issue.title || JSON.stringify(issue)}</Typography>
                    {issue.displayValue && (
                      <Typography variant="caption" color="text.secondary">{issue.displayValue}</Typography>
                    )}
                  </Box>
                </Box>
              ))}
            </Stack>
          </CardContent>
        </Card>
      )}
    </Stack>
  );
}

// ── SEO Full Tab — real Apollo data ──────────────────────────────────────────
function SeoCheckRow({ label, ok, detail }) {
  return (
    <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, py: 1.25, borderBottom: '1px solid #EDE8FC' }}>
      {ok
        ? <CheckCircle sx={{ fontSize: 18, color: '#16A34A', flexShrink: 0 }} />
        : <Cancel sx={{ fontSize: 18, color: '#DC2626', flexShrink: 0 }} />}
      <Box sx={{ flex: 1 }}>
        <Typography variant="body2" fontWeight={600}>{label}</Typography>
        {detail && <Typography variant="caption" color={ok ? 'text.secondary' : 'error.main'}>{detail}</Typography>}
      </Box>
    </Box>
  );
}

function SeoTab({ siteId }) {
  const { data, loading } = useQuery(GET_SITE_SEO, {
    variables: { site_id: siteId },
    skip: !siteId,
  });
  const seo = data?.getSiteSeo;

  if (loading) {
    return (
      <Grid container spacing={2.5}>
        <Grid item xs={12} lg={8}><Skeleton variant="rounded" height={360} /></Grid>
        <Grid item xs={12} lg={4}><Skeleton variant="rounded" height={220} /></Grid>
      </Grid>
    );
  }

  if (!seo) {
    return (
      <Alert severity="info" sx={{ borderRadius: 2 }}>
        No SEO data yet. Add Google PageSpeed API key in <strong>Portal Settings → API Integrations</strong>, then run a health check.
      </Alert>
    );
  }

  const score = seo.overall_score ?? 0;
  const scoreColor = score >= 80 ? '#16A34A' : score >= 50 ? '#D97706' : '#DC2626';
  const imagesWithAlt = (seo.images_total ?? 0) - (seo.images_no_alt ?? 0);
  const altPct = seo.images_total > 0 ? Math.round((imagesWithAlt / seo.images_total) * 100) : 0;

  const checks = [
    { label: 'Page Title', ok: seo.has_title, detail: seo.has_title ? `${seo.title_length} chars` : 'Missing title tag' },
    { label: 'Meta Description', ok: seo.has_meta_desc, detail: seo.has_meta_desc ? `${seo.meta_desc_length} chars` : 'Missing meta description' },
    { label: 'H1 Tag', ok: seo.h1_count === 1, detail: `${seo.h1_count ?? 0} H1 tag${seo.h1_count !== 1 ? 's' : ''} found` },
    { label: 'Canonical Tag', ok: seo.has_canonical, detail: seo.has_canonical ? 'Present' : 'Missing canonical tag' },
    { label: 'OG / Social Tags', ok: seo.has_og_tags, detail: seo.has_og_tags ? 'OG tags found' : 'OG tags missing' },
    { label: 'Structured Data', ok: seo.has_structured_data, detail: seo.has_structured_data ? 'JSON-LD / Schema found' : 'No structured data found' },
  ];

  const googleServices = [
    { label: 'GA4', detected: seo.has_ga4 ?? false },
    { label: 'GTM', detected: seo.has_gtm ?? false },
    { label: 'Google Ads', detected: seo.has_google_ads ?? false },
    { label: 'reCAPTCHA', detected: seo.has_recaptcha ?? false },
  ];

  return (
    <Grid container spacing={2.5}>
      <Grid item xs={12} lg={8}>
        <Stack spacing={2.5}>
          <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
            <CardContent sx={{ p: 3 }}>
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 3, mb: 2.5 }}>
                <Box sx={{
                  width: 80, height: 80, borderRadius: '50%', border: `7px solid ${scoreColor}`,
                  bgcolor: `${scoreColor}11`, display: 'flex', alignItems: 'center', justifyContent: 'center',
                }}>
                  <Typography fontWeight={800} fontSize={22} color={scoreColor}>{score}</Typography>
                </Box>
                <Box>
                  <Typography variant="h6" fontWeight={700}>SEO Score</Typography>
                  <Typography variant="body2" color="text.secondary">Based on {checks.length} checks</Typography>
                  {seo.scanned_at && <Typography variant="caption" color="text.disabled">Scanned {fmtDatetime(seo.scanned_at)}</Typography>}
                </Box>
              </Box>
              {checks.map((c) => <SeoCheckRow key={c.label} {...c} />)}
            </CardContent>
          </Card>
          {seo.images_total > 0 && (
            <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
              <CardContent sx={{ p: 3 }}>
                <Typography variant="h6" fontWeight={700} sx={{ mb: 2 }}>Images</Typography>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
                  <Typography variant="body2" color="text.secondary">{imagesWithAlt} / {seo.images_total} images have alt text</Typography>
                  <Typography variant="body2" fontWeight={700} color={altPct === 100 ? 'success.main' : 'warning.main'}>{altPct}%</Typography>
                </Box>
                <LinearProgress variant="determinate" value={altPct} sx={{ borderRadius: 2, height: 8, bgcolor: '#DDD4F8' }} color={altPct >= 90 ? 'success' : 'warning'} />
              </CardContent>
            </Card>
          )}
          <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
            <CardContent sx={{ p: 3 }}>
              <Typography variant="h6" fontWeight={700} sx={{ mb: 2 }}>Links</Typography>
              <Box sx={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 1.5 }}>
                {[['Internal', seo.links_internal ?? 0, '#8E43F0'], ['External', seo.links_external ?? 0, '#8E43F0'], ['No anchor text', seo.links_no_text ?? 0, '#DC2626']].map(([label, count, color]) => (
                  <Box key={label} sx={{ p: 1.5, bgcolor: '#F7F5FF', borderRadius: 1.5, textAlign: 'center', border: '1px solid #DDD4F8' }}>
                    <Typography variant="h5" fontWeight={800} color={color}>{count}</Typography>
                    <Typography variant="caption" color="text.secondary">{label}</Typography>
                  </Box>
                ))}
              </Box>
              {seo.word_count > 0 && (
                <Typography variant="caption" color="text.disabled" sx={{ mt: 1.5, display: 'block' }}>
                  {seo.word_count.toLocaleString()} words · ~{Math.ceil(seo.word_count / 200)} min read
                </Typography>
              )}
            </CardContent>
          </Card>
        </Stack>
      </Grid>
      <Grid item xs={12} lg={4}>
        <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
          <CardContent sx={{ p: 3 }}>
            <Typography variant="h6" fontWeight={700} sx={{ mb: 2 }}>Google Services</Typography>
            <Stack spacing={1.5}>
              {googleServices.map((svc) => (
                <Box key={svc.label} sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  {svc.detected
                    ? <CheckCircle sx={{ fontSize: 16, color: '#16A34A' }} />
                    : <Cancel sx={{ fontSize: 16, color: '#9B89C4' }} />}
                  <Typography variant="body2" fontWeight={svc.detected ? 600 : 400} color={svc.detected ? 'text.primary' : 'text.secondary'}>
                    {svc.label}
                  </Typography>
                  {svc.detected && <Chip label="Detected" size="small" sx={{ height: 18, fontSize: '0.65rem', bgcolor: '#DCFCE7', color: '#15803D', fontWeight: 600, ml: 'auto' }} />}
                </Box>
              ))}
            </Stack>
          </CardContent>
        </Card>
      </Grid>
    </Grid>
  );
}

// ── AA Plugin Sub-Panel ────────────────────────────────────────────────────────
function AaTokenSubPanel({ site, notify }) {
  const [token, setToken] = useState('');
  const [showTok, setShowTok] = useState(false);

  const [saveToken, { loading: saving }] = useMutation(UPDATE_WP_SITE_AA_TOKEN, {
    onCompleted: () => { setToken(''); notify('Active Auditor token saved and verified.', 'success'); },
    onError: (e) => notify(e.message, 'error'),
    refetchQueries: [{ query: GET_WP_SITE, variables: { id: site.id } }],
  });

  const [verifyToken, { loading: verifying }] = useMutation(VERIFY_WP_SITE_AA_TOKEN, {
    onCompleted: () => notify('Token re-verified successfully.', 'success'),
    onError: (e) => notify(e.message, 'error'),
    refetchQueries: [{ query: GET_WP_SITE, variables: { id: site.id } }],
  });

  const configured = site.aa_plugin_active ?? false;
  const version    = site.aa_plugin_version ?? null;
  const hint       = site.aa_token_hint ?? null;
  const verifiedAt = site.aa_token_verified_at ?? null;

  return (
    <Stack spacing={1.5}>
      {/* Status badge */}
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
        <Extension sx={{ fontSize: 14, color: configured ? '#16A34A' : '#9B89C4' }} />
        <Typography variant="caption" color={configured ? 'success.main' : 'text.secondary'} fontWeight={600}>
          {configured ? `Active Auditor v${version || '?'} detected` : 'Plugin not detected on this site'}
        </Typography>
      </Box>

      {/* Token saved state */}
      {hint ? (
        <Stack spacing={1}>
          <Box sx={{ p: 1, bgcolor: '#F7F5FF', borderRadius: 1.5, border: '1px solid #DDD4F8' }}>
            <Typography variant="caption" color="text.secondary" sx={{ fontFamily: 'monospace', display: 'block' }}>
              Token: ••••••{hint}
            </Typography>
            {verifiedAt && (
              <Typography variant="caption" color="text.disabled" sx={{ fontSize: '0.65rem' }}>
                Verified {formatDistanceToNow(new Date(verifiedAt), { addSuffix: true })}
              </Typography>
            )}
          </Box>
          <Stack direction="row" spacing={1}>
            <Button
              size="small" variant="outlined" fullWidth
              disabled={verifying}
              startIcon={verifying ? <CircularProgress size={10} color="inherit" /> : <Refresh sx={{ fontSize: 13 }} />}
              onClick={() => verifyToken({ variables: { id: site.id } })}
              sx={{ borderRadius: 1.5, fontSize: '0.7rem', py: 0.4 }}
            >
              {verifying ? 'Verifying…' : 'Re-verify'}
            </Button>
          </Stack>
        </Stack>
      ) : null}

      {/* New token input */}
      <TextField
        label={hint ? 'Replace Token' : 'Plugin API Token'}
        size="small" fullWidth
        type={showTok ? 'text' : 'password'}
        value={token}
        onChange={(e) => setToken(e.target.value)}
        placeholder="Paste token from WP Admin → Active Auditor → Settings"
        sx={{ '& .MuiInputBase-input': { fontSize: '0.78rem' } }}
        InputProps={{
          endAdornment: (
            <InputAdornment position="end">
              <IconButton size="small" onClick={() => setShowTok(!showTok)} edge="end">
                {showTok ? <VisibilityOff sx={{ fontSize: 14 }} /> : <Visibility sx={{ fontSize: 14 }} />}
              </IconButton>
            </InputAdornment>
          ),
        }}
      />
      <Button
        variant="contained" size="small" fullWidth
        disabled={!token.trim() || saving}
        startIcon={saving ? <CircularProgress size={11} color="inherit" /> : <Save sx={{ fontSize: 13 }} />}
        onClick={() => saveToken({ variables: { id: site.id, aa_token: token.trim() } })}
        sx={{ borderRadius: 1.5, fontSize: '0.73rem', py: 0.55, bgcolor: '#8E43F0', '&:hover': { bgcolor: '#7330D4' } }}
      >
        {saving ? 'Verifying & Saving…' : 'Verify & Save Token'}
      </Button>

      <Typography variant="caption" color="text.disabled" sx={{ fontSize: '0.65rem', lineHeight: 1.4 }}>
        Full data access: health, updates, security, Lighthouse, SEO &amp; Google Services.
      </Typography>
    </Stack>
  );
}

// ── WP Admin Auth Sub-Panel ────────────────────────────────────────────────────
function WpAuthSubPanel({ site, notify }) {
  const [authMethod, setAuthMethod]   = useState(site.auth_method ?? 'app_password');
  const [authUsername, setAuthUsername] = useState(site.auth_username ?? '');
  const [authToken, setAuthToken]     = useState('');
  const [showToken, setShowToken]     = useState(false);

  const [saveAuth, { loading: saving }] = useMutation(UPDATE_WP_SITE_AUTH, {
    onCompleted: () => { setAuthToken(''); notify('WP credentials saved.', 'success'); },
    onError: (e) => notify(e.message, 'error'),
    refetchQueries: [{ query: GET_WP_SITE, variables: { id: site.id } }],
  });

  const [verifyAuth, { loading: verifying }] = useMutation(VERIFY_WP_SITE_TOKEN, {
    onCompleted: () => notify('WP credentials verified — administrator role confirmed.', 'success'),
    onError: (e) => notify(e.message, 'error'),
    refetchQueries: [{ query: GET_WP_SITE, variables: { id: site.id } }],
  });

  const [removeAuth, { loading: removing }] = useMutation(REMOVE_WP_SITE_AUTH, {
    onCompleted: () => notify('WP credentials removed.', 'info'),
    onError: (e) => notify(e.message, 'error'),
    refetchQueries: [{ query: GET_WP_SITE, variables: { id: site.id } }],
  });

  const { confirm } = useConfirmation();
  const isAuthenticated = site.is_authenticated ?? false;
  const hint = site.auth_token_hint ?? null;
  const verifiedAt = site.token_verified_at ?? null;

  const handleRemove = async () => {
    const ok = await confirm({
      title: 'Remove WP Credentials',
      message: 'This will clear the saved username and application password for this site.',
      confirmText: 'Remove',
      severity: 'warning',
    });
    if (ok) removeAuth({ variables: { id: site.id } });
  };

  return (
    <Stack spacing={1.5}>
      {/* Verification status */}
      {isAuthenticated && (
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, p: 1, bgcolor: verifiedAt ? '#F0FDF4' : '#FFF7ED', borderRadius: 1.5, border: `1px solid ${verifiedAt ? '#BBF7D0' : '#FED7AA'}` }}>
          {verifiedAt
            ? <VerifiedUser sx={{ fontSize: 14, color: '#16A34A' }} />
            : <Warning sx={{ fontSize: 14, color: '#D97706' }} />
          }
          <Box sx={{ flex: 1, minWidth: 0 }}>
            <Typography variant="caption" fontWeight={700} color={verifiedAt ? 'success.main' : 'warning.main'} sx={{ display: 'block' }}>
              {verifiedAt ? 'Verified as administrator' : 'Unverified credentials'}
            </Typography>
            <Typography variant="caption" color="text.disabled" sx={{ fontSize: '0.62rem', fontFamily: 'monospace' }}>
              {site.auth_username} · ••••{hint}
              {verifiedAt ? ` · ${formatDistanceToNow(new Date(verifiedAt), { addSuffix: true })}` : ''}
            </Typography>
          </Box>
        </Box>
      )}

      {/* Auth Method */}
      <FormControl size="small" fullWidth>
        <InputLabel sx={{ fontSize: '0.78rem' }}>Auth Method</InputLabel>
        <Select
          value={authMethod}
          label="Auth Method"
          onChange={(e) => setAuthMethod(e.target.value)}
          sx={{ fontSize: '0.78rem' }}
        >
          <MenuItem value="app_password">Application Password</MenuItem>
          <MenuItem value="jwt">JWT Token</MenuItem>
          <MenuItem value="basic">HTTP Basic</MenuItem>
        </Select>
      </FormControl>

      {/* Username */}
      <TextField
        label="WP Username / Email"
        size="small" fullWidth
        value={authUsername}
        onChange={(e) => setAuthUsername(e.target.value)}
        placeholder="admin"
        sx={{ '& .MuiInputBase-input': { fontSize: '0.78rem' } }}
      />

      {/* Token / Password */}
      <TextField
        label={authMethod === 'app_password' ? 'Application Password' : 'Token / Password'}
        size="small" fullWidth
        type={showToken ? 'text' : 'password'}
        value={authToken}
        onChange={(e) => setAuthToken(e.target.value)}
        placeholder={isAuthenticated ? 'Leave blank to keep current' : 'xxxx xxxx xxxx xxxx xxxx xxxx'}
        sx={{ '& .MuiInputBase-input': { fontSize: '0.78rem' } }}
        InputProps={{
          endAdornment: (
            <InputAdornment position="end">
              <IconButton size="small" onClick={() => setShowToken(!showToken)} edge="end">
                {showToken ? <VisibilityOff sx={{ fontSize: 14 }} /> : <Visibility sx={{ fontSize: 14 }} />}
              </IconButton>
            </InputAdornment>
          ),
        }}
      />

      {/* Actions */}
      <Stack direction="row" spacing={1}>
        <Button
          variant="contained" size="small" fullWidth
          disabled={!authUsername.trim() || !authToken.trim() || saving}
          startIcon={saving ? <CircularProgress size={11} color="inherit" /> : <Key sx={{ fontSize: 13 }} />}
          onClick={() => saveAuth({ variables: { id: site.id, auth_method: authMethod, auth_username: authUsername, auth_token: authToken } })}
          sx={{ borderRadius: 1.5, fontSize: '0.7rem', py: 0.55 }}
        >
          {saving ? 'Saving…' : 'Save'}
        </Button>
        {isAuthenticated && (
          <Button
            variant="outlined" size="small"
            disabled={verifying}
            startIcon={verifying ? <CircularProgress size={11} color="inherit" /> : <VerifiedUser sx={{ fontSize: 13 }} />}
            onClick={() => verifyAuth({ variables: { id: site.id } })}
            sx={{ borderRadius: 1.5, fontSize: '0.7rem', py: 0.55, whiteSpace: 'nowrap' }}
          >
            {verifying ? '…' : 'Verify'}
          </Button>
        )}
      </Stack>

      {isAuthenticated && (
        <Button
          variant="text" color="error" size="small" fullWidth
          disabled={removing}
          startIcon={<LinkOff sx={{ fontSize: 13 }} />}
          onClick={handleRemove}
          sx={{ fontSize: '0.7rem', py: 0.3 }}
        >
          {removing ? 'Removing…' : 'Remove Credentials'}
        </Button>
      )}

      <Typography variant="caption" color="text.disabled" sx={{ fontSize: '0.65rem', lineHeight: 1.4 }}>
        Limited data: basic WP info only. Install Active Auditor plugin for full monitoring.
      </Typography>
    </Stack>
  );
}

// ── Unified Site Connection Panel ─────────────────────────────────────────────
function SiteConnectionPanel({ site, notify }) {
  const aaActive = site.aa_plugin_active ?? false;
  const wpAuthed = site.is_authenticated ?? false;
  const [connTab, setConnTab] = useState(aaActive ? 0 : 1);

  const statusLabel  = aaActive ? 'Plugin Connected' : wpAuthed ? 'WP Auth' : 'Not Connected';
  const statusBg     = aaActive ? '#DCFCE7' : wpAuthed ? '#FEF9C3' : '#FEE2E2';
  const statusColor  = aaActive ? '#15803D'  : wpAuthed ? '#92400E' : '#DC2626';

  return (
    <Card sx={{ borderRadius: 2, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
      <CardContent sx={{ p: 2 }}>
        {/* Header */}
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1.5 }}>
          <LinkIcon sx={{ fontSize: 15, color: 'text.secondary' }} />
          <Typography variant="caption" fontWeight={700} color="text.secondary" sx={{ textTransform: 'uppercase', letterSpacing: '0.08em' }}>
            Site Connection
          </Typography>
          <Chip
            label={statusLabel}
            size="small"
            sx={{ ml: 'auto', height: 18, fontSize: '0.62rem', fontWeight: 700, bgcolor: statusBg, color: statusColor }}
          />
        </Box>

        {/* Sub-tabs: AA Plugin | WP Admin */}
        <Box sx={{ borderBottom: '1px solid #EDE8FC', mb: 1.5 }}>
          <Tabs value={connTab} onChange={(_, v) => setConnTab(v)} sx={{ minHeight: 30, '& .MuiTab-root': { minHeight: 30, py: 0, fontSize: '0.72rem', fontWeight: 600 } }}>
            <Tab label="AA Plugin" sx={{ flex: 1 }} />
            <Tab label="WP Admin" sx={{ flex: 1 }} />
          </Tabs>
        </Box>

        {connTab === 0 && <AaTokenSubPanel site={site} notify={notify} />}
        {connTab === 1 && <WpAuthSubPanel site={site} notify={notify} />}
      </CardContent>
    </Card>
  );
}

function GoogleServicesCard({ siteId }) {
  const { data: gsData } = useQuery(GET_SITE_GOOGLE_SERVICES, {
    variables: { site_id: siteId },
    skip: !siteId,
  });
  const gs = gsData?.getSiteGoogleServices;

  const services = [
    { label: 'GA4',           detected: gs?.has_ga4 ?? false },
    { label: 'GTM',           detected: gs?.has_gtm ?? false },
    { label: 'Google Ads',    detected: gs?.has_google_ads ?? false },
    { label: 'reCAPTCHA',     detected: gs?.has_recaptcha ?? false },
    { label: 'Google Maps',   detected: gs?.has_maps ?? false },
    { label: 'Google Fonts',  detected: gs?.has_fonts ?? false },
    { label: 'Search Console',detected: gs?.has_search_console ?? false },
  ];

  return (
    <Card sx={{ borderRadius: 2, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
      <CardContent sx={{ p: 2 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1.5 }}>
          <Analytics sx={{ fontSize: 16, color: 'text.secondary' }} />
          <Typography variant="caption" fontWeight={700} color="text.secondary" sx={{ textTransform: 'uppercase', letterSpacing: '0.08em' }}>
            Google Services
          </Typography>
          {gs && (
            <Chip label={`${gs.total_services ?? 0} active`} size="small"
              sx={{ ml: 'auto', height: 16, fontSize: '0.6rem', fontWeight: 700, bgcolor: '#EDE8FC', color: '#5C4A8A' }} />
          )}
        </Box>
        <Stack spacing={0.8}>
          {services.map((svc) => (
            <Box key={svc.label} sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
              {svc.detected
                ? <CheckCircle sx={{ fontSize: 13, color: '#16A34A', flexShrink: 0 }} />
                : <Cancel sx={{ fontSize: 13, color: '#C4B5D8', flexShrink: 0 }} />}
              <Typography variant="caption" color={svc.detected ? 'success.main' : 'text.disabled'} fontWeight={svc.detected ? 600 : 400} sx={{ fontSize: '0.75rem' }}>
                {svc.label}
              </Typography>
            </Box>
          ))}
        </Stack>
        {gs?.scanned_at && (
          <Typography variant="caption" color="text.disabled" sx={{ mt: 1, display: 'block', fontSize: '0.62rem' }}>
            Scanned {formatDistanceToNow(new Date(gs.scanned_at), { addSuffix: true })}
          </Typography>
        )}
        {!gs && (
          <Typography variant="caption" color="text.disabled" sx={{ fontSize: '0.68rem' }}>
            Run a health check to detect Google Services.
          </Typography>
        )}
      </CardContent>
    </Card>
  );
}

function ActiveThemeCard({ theme: themeProp, siteId, confirm, notify }) {
  const [theme, setTheme] = useState(
    themeProp ?? { name: 'Astra', version: '4.5.0', latest: '4.5.2', updateAvailable: !!themeProp }
  );
  const [updating, setUpdating] = useState(false);

  const [triggerUpdate] = useMutation(TRIGGER_SITE_UPDATE, {
    onCompleted: () => { notify('Theme updated — refreshing data…', 'success'); setUpdating(false); },
    onError: (e) => { notify(e.message, 'error'); setUpdating(false); },
  });

  const handleUpdateTheme = async () => {
    const ok = await confirm({
      title: 'Update Theme',
      message: `Update ${theme.name ?? theme.slug} from v${theme.version} to v${theme.latest ?? theme.new_version}? The site may be briefly unavailable during the update.`,
      confirmText: 'Update Theme',
      severity: 'warning',
    });
    if (!ok) return;
    setUpdating(true);
    if (siteId && theme.slug) {
      triggerUpdate({ variables: { id: siteId, type: 'theme', item_slug: theme.slug } });
    } else {
      // fallback for mock data
      await new Promise((r) => setTimeout(r, 1800));
      setTheme((t) => ({ ...t, version: t.latest, updateAvailable: false }));
      setUpdating(false);
    }
  };

  return (
    <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
      <CardContent sx={{ p: 3 }}>
        <Typography variant="h6" fontWeight={700} sx={{ mb: 1.5 }}>Active Theme</Typography>
        <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 1.5 }}>
          <Box>
            <Typography variant="body1" fontWeight={600}>{theme.name}</Typography>
            {theme.updateAvailable ? (
              <Typography variant="caption" color="text.secondary">
                Version: <Box component="span" sx={{ fontFamily: 'monospace' }}>{theme.version}</Box>
                {' → '}
                <Box component="span" sx={{ fontFamily: 'monospace', color: '#854D0E', fontWeight: 700 }}>{theme.latest}</Box>
                {' '}
                <Chip label="Update Available" size="small" sx={{ bgcolor: '#FEF9C3', color: '#92400E', fontWeight: 600, height: 18, fontSize: '0.68rem' }} />
              </Typography>
            ) : (
              <Typography variant="caption" color="success.main" fontWeight={600}>
                v{theme.version} — Up to date ✓
              </Typography>
            )}
          </Box>
          {theme.updateAvailable && (
            <Button
              variant="contained" size="small"
              disabled={updating}
              startIcon={updating ? <CircularProgress size={14} color="inherit" /> : <Update />}
              onClick={handleUpdateTheme}
              sx={{ borderRadius: 2, fontWeight: 600, bgcolor: '#8E43F0' }}
            >
              {updating ? 'Updating…' : 'Update Theme'}
            </Button>
          )}
        </Box>
      </CardContent>
    </Card>
  );
}

// ── Themes tab ────────────────────────────────────────────────────────────────
function ThemesList({ initialThemes, siteId, confirm, notify }) {
  const [themes, setThemes] = useState(initialThemes);
  const [updating, setUpdating] = useState({});

  const updatableThemes = themes.filter((t) => t.updateAvailable);

  const [triggerUpdate] = useMutation(TRIGGER_SITE_UPDATE, {
    onCompleted: () => notify('Theme updated — refreshing data…', 'success'),
    onError: (e) => notify(e.message, 'error'),
  });

  const handleUpdate = async (theme) => {
    const ok = await confirm({
      title: `Update ${theme.name}`,
      message: `Update ${theme.name} from v${theme.version} to v${theme.latestVersion}? The site may be briefly unavailable.`,
      confirmText: 'Update Theme',
      severity: 'warning',
    });
    if (!ok) return;
    setUpdating((prev) => ({ ...prev, [theme.slug]: true }));
    try {
      await triggerUpdate({ variables: { id: siteId, type: 'theme', item_slug: theme.slug } });
      // Mark theme as up-to-date immediately in UI
      setThemes((prev) => prev.map((t) =>
        t.slug === theme.slug ? { ...t, updateAvailable: false, version: t.latestVersion } : t
      ));
    } catch (e) {
      // error shown by onError
    } finally {
      setUpdating((prev) => ({ ...prev, [theme.slug]: false }));
    }
  };


  return (
    <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', p: 2.5, pb: 1.5, borderBottom: '1px solid #EDE8FC' }}>
        <Typography variant="h6" fontWeight={700}>Themes ({themes.length})</Typography>
        {updatableThemes.length > 0 && (
          <Chip label={`${updatableThemes.length} update${updatableThemes.length > 1 ? 's' : ''} available`} size="small"
            sx={{ bgcolor: '#FEF3C7', color: '#92400E', fontWeight: 600 }} />
        )}
      </Box>
      <TableContainer>
        <Table>
          <TableHead>
            <TableRow sx={{ bgcolor: '#F7F5FF' }}>
              {['Theme', 'Status', 'Installed', 'Latest', 'Update'].map((h) => (
                <TableCell key={h} sx={{ fontWeight: 700, fontSize: '0.78rem', color: 'text.secondary', textTransform: 'uppercase', letterSpacing: '0.06em' }}>{h}</TableCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            {themes.map((t) => (
              <TableRow key={t.id} hover>
                <TableCell>
                  <Typography variant="body2" fontWeight={600}>{t.name}</Typography>
                  <Typography variant="caption" color="text.secondary" sx={{ fontFamily: 'monospace', fontSize: '0.7rem' }}>{t.slug}</Typography>
                </TableCell>
                <TableCell>
                  <Chip label={t.active ? 'Active' : 'Installed'} size="small"
                    sx={{ bgcolor: t.active ? '#DCFCE7' : '#EDE8FC', color: t.active ? '#15803D' : '#5C4A8A', fontWeight: 600, height: 22 }} />
                </TableCell>
                <TableCell><Typography variant="body2" color="text.secondary" sx={{ fontFamily: 'monospace' }}>{t.version}</Typography></TableCell>
                <TableCell>
                  <Typography variant="body2" sx={{ fontFamily: 'monospace', color: t.updateAvailable ? '#854D0E' : 'text.secondary', fontWeight: t.updateAvailable ? 700 : 400 }}>
                    {t.latestVersion}
                  </Typography>
                </TableCell>
                <TableCell>
                  {t.updateAvailable ? (
                    <Button size="small" variant="text" color="primary"
                      onClick={() => handleUpdate(t)}
                      disabled={!!updating[t.slug]}
                      startIcon={updating[t.slug] ? <CircularProgress size={12} /> : null}
                      sx={{ fontWeight: 600, fontSize: '0.8rem', p: '2px 8px', textDecoration: 'underline', '&:hover': { bgcolor: 'transparent' } }}
                    >
                      {updating[t.slug] ? 'Updating…' : 'Update'}
                    </Button>
                  ) : (
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                      <CheckCircle sx={{ fontSize: 14, color: '#16A34A' }} />
                      <Typography variant="caption" color="success.main" fontWeight={600}>Up to Date</Typography>
                    </Box>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
    </Card>
  );
}

// ── Plugins tab ───────────────────────────────────────────────────────────────
function PluginsList({ initialPlugins, siteId, confirm, notify }) {
  const [plugins, setPlugins] = useState(initialPlugins);
  const [updating, setUpdating] = useState({});  // id → true while updating
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  const updatablePlugins = plugins.filter((p) => p.updateAvailable);
  const filteredPlugins = plugins.filter((p) =>
    !searchQuery ||
    p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    (p.slug ?? '').toLowerCase().includes(searchQuery.toLowerCase())
  );

  const [triggerUpdate, { loading: mutationLoading }] = useMutation(TRIGGER_SITE_UPDATE, {
    onCompleted: () => { notify('Update completed — refreshing data…', 'success'); },
    onError: (e) => notify(e.message, 'error'),
  });

  const doUpdate = async (ids) => {
    setUpdating((prev) => Object.fromEntries(ids.map((id) => [id, true]).concat(Object.entries(prev))));
    const successIds = [];
    try {
      await Promise.all(ids.map(async (slug) => {
        try {
          await triggerUpdate({ variables: { id: siteId, type: 'plugin', item_slug: slug } });
          successIds.push(slug);
        } catch (e) {
          // per-plugin error already shown by onError
        }
      }));
    } finally {
      setUpdating({});
      // Immediately mark successfully updated plugins as up-to-date in UI
      if (successIds.length > 0) {
        setPlugins((prev) => prev.map((p) =>
          successIds.includes(p.id)
            ? { ...p, updateAvailable: false, version: p.latestVersion }
            : p
        ));
      }
    }
  };


  const handleUpdate = async (plugin) => {
    const ok = await confirm({
      title: `Update ${plugin.name}`,
      message: `Update ${plugin.name} from v${plugin.version} to v${plugin.latestVersion}? The site may be briefly unavailable.`,
      confirmText: 'Update Plugin',
      severity: 'warning',
    });
    if (ok) doUpdate([plugin.id]);
  };

  const handleUpdateAll = async () => {
    const ok = await confirm({
      title: `Update All Plugins (${updatablePlugins.length})`,
      message: `Update ${updatablePlugins.length} plugin${updatablePlugins.length > 1 ? 's' : ''} to their latest versions? The site may be briefly unavailable during updates.`,
      confirmText: `Update All (${updatablePlugins.length})`,
      severity: 'warning',
    });
    if (ok) doUpdate(updatablePlugins.map((p) => p.id));
  };

  const handleRefresh = async () => {
    setRefreshing(true);
    try {
      await triggerUpdate({ variables: { id: siteId, type: 'core', item_slug: null } }).catch(() => {});
    } finally {
      setRefreshing(false);
    }
  };

  return (
    <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
      {/* Header */}
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', p: 2.5, pb: 1.5, borderBottom: '1px solid #EDE8FC', flexWrap: 'wrap', gap: 1.5 }}>
        <Typography variant="h6" fontWeight={700}>
          Plugins ({plugins.length})
        </Typography>
        <Stack direction="row" spacing={1} alignItems="center">
          <TextField
            size="small"
            placeholder="Search plugins…"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <SearchIcon sx={{ fontSize: 16, color: 'text.secondary' }} />
                </InputAdornment>
              ),
            }}
            sx={{ width: 200, '& .MuiInputBase-input': { fontSize: '0.82rem', py: 0.7 } }}
          />
          <Button variant="outlined" size="small" startIcon={refreshing ? <CircularProgress size={12} /> : <Refresh />} onClick={handleRefresh} disabled={refreshing} sx={{ borderRadius: 2 }}>
            Refresh
          </Button>
          {updatablePlugins.length > 0 && (
            <Button variant="contained" size="small" startIcon={<Update />} onClick={handleUpdateAll} sx={{ borderRadius: 2 }}>
              Update All ({updatablePlugins.length})
            </Button>
          )}
        </Stack>
      </Box>
      {/* BUG-09: stale data alert when site reports updates but plugin list shows none */}
      {updatablePlugins.length === 0 && plugins.length > 0 && (
        <Alert severity="info" sx={{ m: 2, mb: 0, borderRadius: 2 }}>
          Run a fresh health check to fetch the latest plugin update information.
        </Alert>
      )}
      <TableContainer>
        <Table>
          <TableHead>
            <TableRow sx={{ bgcolor: '#F7F5FF' }}>
              {['Plugin', 'Status', 'Installed', 'Latest', 'Update'].map((h) => (
                <TableCell key={h} sx={{ fontWeight: 700, fontSize: '0.78rem', color: 'text.secondary', textTransform: 'uppercase', letterSpacing: '0.06em' }}>{h}</TableCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            {filteredPlugins.length === 0 && searchQuery && (
              <TableRow>
                <TableCell colSpan={5} sx={{ textAlign: 'center', py: 4, color: 'text.secondary' }}>
                  No plugins match "{searchQuery}"
                </TableCell>
              </TableRow>
            )}
            {filteredPlugins.map((p) => (
              <TableRow key={p.id} hover>
                <TableCell>
                  <Typography variant="body2" fontWeight={600}>{p.name}</Typography>
                  {/* BUG-13: show real WP file path, fall back to slug */}
                  <Typography variant="caption" color="text.secondary" sx={{ fontFamily: 'monospace', fontSize: '0.7rem' }}>
                    {p.pluginFile || p.path || p.slug || p.id}
                  </Typography>
                </TableCell>
                <TableCell>
                  <Chip label={p.active ? 'Active' : 'Inactive'} size="small"
                    sx={{ bgcolor: p.active ? '#DCFCE7' : '#EDE8FC', color: p.active ? '#15803D' : '#5C4A8A', fontWeight: 600, height: 22 }} />
                </TableCell>
                <TableCell><Typography variant="body2" color="text.secondary" sx={{ fontFamily: 'monospace' }}>{p.version}</Typography></TableCell>
                <TableCell>
                  <Typography variant="body2" sx={{ fontFamily: 'monospace', color: p.updateAvailable ? '#854D0E' : 'text.secondary', fontWeight: p.updateAvailable ? 700 : 400 }}>
                    {p.latestVersion}
                  </Typography>
                </TableCell>
                <TableCell>
                  {p.updateAvailable ? (
                    <Button
                      size="small" variant="text" color="primary"
                      onClick={() => handleUpdate(p)}
                      disabled={!!updating[p.id]}
                      startIcon={updating[p.id] ? <CircularProgress size={12} /> : null}
                      sx={{ fontWeight: 600, fontSize: '0.8rem', p: '2px 8px', textDecoration: 'underline', '&:hover': { bgcolor: 'transparent' } }}
                    >
                      {updating[p.id] ? 'Updating…' : 'Update'}
                    </Button>
                  ) : (
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                      <CheckCircle sx={{ fontSize: 14, color: '#16A34A' }} />
                      <Typography variant="caption" color="success.main" fontWeight={600}>Up to Date</Typography>
                    </Box>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
    </Card>
  );
}

// ── Security tab ──────────────────────────────────────────────────────────────
function SecurityTab({ siteId, issues = [] }) {
  const sevColor = { high: '#DC2626', medium: '#D97706', low: '#8E43F0' };

  if (!issues.length)
    return (
      <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none', p: 3, textAlign: 'center' }}>
        <CheckCircle sx={{ fontSize: 40, color: '#16A34A', mb: 1 }} />
        <Typography variant="h6" fontWeight={600} color="success.main">No security issues found</Typography>
        <Typography variant="body2" color="text.secondary">This site passed all security checks.</Typography>
      </Card>
    );

  return (
    <Stack spacing={2}>
      {issues.map((issue) => (
        <Card key={issue.id} sx={{ borderRadius: 3, border: `1px solid ${sevColor[issue.severity] ?? '#DDD4F8'}40`, boxShadow: 'none' }}>
          <CardContent sx={{ p: 2.5 }}>
            <Box sx={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 2 }}>
              <Box>
                <Chip label={issue.severity.toUpperCase()} size="small"
                  sx={{ bgcolor: `${sevColor[issue.severity]}15`, color: sevColor[issue.severity], fontWeight: 700, height: 20, mb: 1 }} />
                <Typography variant="body1" fontWeight={600}>{issue.title}</Typography>
                <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>{issue.description}</Typography>
              </Box>
            </Box>
          </CardContent>
        </Card>
      ))}
    </Stack>
  );
}

// ── Check History tab ─────────────────────────────────────────────────────────
function CheckHistoryTab({ history = [] }) {
  if (!history.length)
    return <Typography color="text.secondary" sx={{ py: 3, textAlign: 'center' }}>No check history yet.</Typography>;

  return (
    <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
      <TableContainer>
        <Table>
          <TableHead>
            <TableRow sx={{ bgcolor: '#F7F5FF' }}>
              {['Started', 'Completed', 'Status', 'Findings'].map((h) => (
                <TableCell key={h} sx={{ fontWeight: 700, fontSize: '0.78rem', color: 'text.secondary', textTransform: 'uppercase', letterSpacing: '0.06em' }}>{h}</TableCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            {history.map((h) => (
              <TableRow key={h.id} hover>
                <TableCell><Typography variant="body2">{fmtDatetime(h.started_at)}</Typography></TableCell>
                {/* BUG-11: Show "In progress" chip when completed_at is null */}
                <TableCell>
                  {h.completed_at
                    ? <Typography variant="body2">{fmtDatetime(h.completed_at)}</Typography>
                    : <Chip label="In progress" size="small" sx={{ bgcolor: '#FEF9C3', color: '#92400E', fontWeight: 600, height: 20, fontSize: '0.72rem' }} />}
                </TableCell>
                <TableCell>
                  <Chip label={h.status === 'success' ? 'Success' : h.status === 'partial' ? 'Partial' : 'Failed'} size="small"
                    sx={{
                      bgcolor: h.status === 'success' ? '#DCFCE7' : h.status === 'partial' ? '#FEF9C3' : '#FEE2E2',
                      color: h.status === 'success' ? '#15803D' : h.status === 'partial' ? '#92400E' : '#DC2626',
                      fontWeight: 600, height: 22,
                    }} />
                </TableCell>
                <TableCell>
                  <Typography variant="body2" fontWeight={h.findings_count > 0 ? 600 : 400} color={h.findings_count > 0 ? 'warning.main' : 'text.secondary'}>
                    {h.findings_count === 0 ? 'None' : `${h.findings_count} finding${h.findings_count > 1 ? 's' : ''}`}
                  </Typography>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
    </Card>
  );
}

// ── Work Items tab ────────────────────────────────────────────────────────────
function WorkItemsTab({ items = [] }) {
  const sevColor = { P0: '#DC2626', P1: '#D97706', P2: '#8E43F0', P3: '#5C4A8A' };
  const statusColor = { open: '#8E43F0', in_progress: '#D97706', acknowledged: '#8E43F0', resolved: '#16A34A' };

  if (!items.length)
    return <Typography color="text.secondary" sx={{ py: 3, textAlign: 'center' }}>No work items for this site.</Typography>;

  return (
    <Stack spacing={2}>
      {items.map((item) => (
        <Card key={item.id} sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
          <CardContent sx={{ p: 2.5 }}>
            <Box sx={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 2 }}>
              <Box sx={{ flex: 1 }}>
                <Stack direction="row" spacing={1} sx={{ mb: 1 }} flexWrap="wrap">
                  <Chip label={item.severity} size="small" sx={{ bgcolor: `${sevColor[item.severity] ?? '#5C4A8A'}20`, color: sevColor[item.severity] ?? '#5C4A8A', fontWeight: 700, height: 20 }} />
                  <Chip label={(item.status || '').replace('_', ' ')} size="small" sx={{ bgcolor: `${statusColor[item.status] ?? '#5C4A8A'}15`, color: statusColor[item.status] ?? '#5C4A8A', fontWeight: 600, height: 20 }} />
                  {/* BUG-10: Display assignee as avatar chip */}
                  {item.assignee && (
                    <Chip
                      avatar={<Avatar sx={{ width: 18, height: 18, fontSize: '0.65rem', bgcolor: '#8E43F0' }}>{(item.assignee?.name ?? item.assignee)?.[0] ?? '?'}</Avatar>}
                      label={item.assignee?.name ?? item.assignee ?? 'Unassigned'}
                      size="small"
                      variant="outlined"
                      sx={{ height: 22, fontSize: '0.68rem' }}
                    />
                  )}
                </Stack>
                <Typography variant="body1" fontWeight={600}>{item.title}</Typography>
                <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>{item.description}</Typography>
              </Box>
            </Box>
          </CardContent>
        </Card>
      ))}
    </Stack>
  );
}

// ── Main SiteDetail Page ──────────────────────────────────────────────────────
export default function SiteDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { confirm } = useConfirmation();
  const { notify, snackbar, closeSnackbar } = useNotification();
  const [tab, setTab] = useState(0);
  const [checkingHealth, setCheckingHealth] = useState(false);
  const [updatingCore, setUpdatingCore] = useState(false);

  const [triggerCoreUpdate] = useMutation(TRIGGER_SITE_UPDATE, {
    onCompleted: () => {
      setUpdatingCore(false);
      notify('WordPress core update started — a health check will refresh the data.', 'success');
    },
    onError: (e) => {
      setUpdatingCore(false);
      notify(e.message || 'Core update failed.', 'error');
    },
  });

  const handleUpdateCore = async () => {
    const ok = await confirm({
      title: 'Update WordPress Core',
      message: `Update WordPress from v${site?.wp_version ?? '?'} to v${coreUpdateVersion}? The site may be briefly unavailable during the update.`,
      confirmText: 'Update WordPress',
      severity: 'warning',
    });
    if (!ok) return;
    setUpdatingCore(true);
    triggerCoreUpdate({ variables: { id: site.id, type: 'core' } });
  };

  const { data, loading, error, refetch } = useQuery(GET_WP_SITE, {
    variables: { id },
    fetchPolicy: 'cache-and-network',
  });

  // ── All hooks must be declared before any early returns ──
  const { data: latestCheckData, refetch: refetchCheck } = useQuery(GET_SITE_LATEST_CHECK, {
    variables: { site_id: id },
    fetchPolicy: 'cache-and-network',
    skip: !id,
  });

  const [runHealthCheck] = useMutation(RUN_SITE_HEALTH_CHECK, {
    onCompleted: () => {
      notify('Health check started — refreshing data…', 'info');
      setTimeout(() => { refetch(); setCheckingHealth(false); }, 2500);
    },
    onError: (e) => {
      notify(e.message || 'Health check failed to start.', 'error');
      setCheckingHealth(false);
    },
  });

  // ── Derived data (safe with undefined latestCheck) ──
  const site = data?.wpSite;
  const latestCheck = latestCheckData?.wpCheckHistory?.[0];
  // Use all_plugins from latest check for full plugin list (not just update-needing ones)
  const plugins = latestCheck?.all_plugins
    ? JSON.parse(latestCheck.all_plugins).map((p, i) => ({
        id: p.path ?? p.slug ?? `p${i}`, // path = WP file path e.g. woocommerce/woocommerce.php
        slug: p.slug,
        name: p.name ?? p.slug,
        version: p.version ?? '—',
        latestVersion: p.new_version ?? p.version ?? '—',
        active: p.active ?? true,
        updateAvailable: !!p.update_available,
      }))
    : latestCheck?.plugins_needing_update
      ? JSON.parse(latestCheck.plugins_needing_update).map((p, i) => ({
          id: p.path ?? p.slug ?? `p${i}`,
          slug: p.slug,
          name: p.name ?? p.slug,
          version: p.version ?? '—',
          latestVersion: p.new_version ?? '—',
          active: true,
          updateAvailable: true,
        }))
      : [];

  // All themes from check history
  const allThemes = latestCheck?.all_themes ? JSON.parse(latestCheck.all_themes) : [];
  const themes = allThemes.map((t, i) => ({
    id: t.slug ?? t.template ?? t.name ?? `t${i}`,
    name: t.name ?? t.slug ?? t.template ?? 'Unknown',
    slug: t.slug ?? t.template ?? t.name?.toLowerCase().replace(/\s+/g, '-') ?? `t${i}`, // backward compat: old data has 'template' not 'slug'
    version: t.version ?? '—',
    latestVersion: t.new_version ?? t.version ?? '—',
    active: !!t.active,
    updateAvailable: !!t.update_available,
  }));


  const themeUpdateCount = themes.filter((t) => t.updateAvailable).length;

  // BUG-09: fall back to site-level plugin_updates count when plugin list has no stale updates flagged
  const updateCount = plugins.filter((p) => p.updateAvailable).length || (site?.plugin_updates ?? 0);

  const themeData = (() => {
    // Try all_themes first (has active flag), fall back to themes_needing_update
    if (latestCheck?.all_themes) {
      const arr = JSON.parse(latestCheck.all_themes);
      return arr.find((t) => t.active) ?? arr[0] ?? null;
    }
    if (latestCheck?.themes_needing_update) {
      const arr = JSON.parse(latestCheck.themes_needing_update);
      return arr[0] ?? null;
    }
    return null;
  })();

  // Core update info
  const coreUpdateVersion = latestCheck?.wp_core_update_version ?? null;

  // ── Early returns AFTER all hooks ──
  if (loading && !site) {
    return (
      <AppLayout>
        <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '50vh' }}>
          <CircularProgress />
        </Box>
      </AppLayout>
    );
  }

  if (error || !site) {
    return (
      <AppLayout>
        <EmptyState
          type="site"
          message={error ? error.message : "Site not found"}
          actionLabel="Back to Sites"
          onAction={() => navigate('/sites')}
        />
      </AppLayout>
    );
  }

  const handleRunHealthCheck = async () => {
    const ok = await confirm({
      title: 'Run Health Check',
      message: `Run a full health check on "${site.name}"? This will scan plugins, PHP version, WordPress core, and security settings.`,
      confirmText: 'Run Health Check',
      cancelText: 'Cancel',
      severity: 'warning',
    });
    if (!ok) return;
    setCheckingHealth(true);
    runHealthCheck({ variables: { id: site.id } });
  };

  const hm = healthMeta[site.overall_health] ?? healthMeta.unknown;

  return (
    <AppLayout>
      <AppSnackbar {...snackbar} onClose={closeSnackbar} />
      <Box sx={{ maxWidth: 1200, mx: 'auto', px: { xs: 2, md: 3 }, py: 3 }}>
        {/* Back link */}
        <Box sx={{ mb: 2 }}>
          <Button startIcon={<ArrowBack />} onClick={() => navigate('/sites')} size="small" sx={{ color: 'text.secondary', fontWeight: 500, '&:hover': { bgcolor: '#EDE8FC' } }}>
            All Sites
          </Button>
        </Box>

        {/* ── Page Header ── */}
        <Box sx={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', mb: 2.5, flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 0.5 }}>
              <Typography variant="h4" fontWeight={800} color="#1A0A3C">{site.name}</Typography>
              <Chip
                label={hm.label}
                size="small"
                sx={{ bgcolor: hm.bg, color: hm.color, fontWeight: 700, border: `1px solid ${hm.color}40`, fontSize: '0.8rem' }}
              />
            </Box>
            <Stack direction="row" alignItems="center" spacing={1}>
              <Typography variant="body2" color="text.secondary">{site.wp_client?.name || 'Unassigned'}</Typography>
              <Box component="span" color="text.disabled">•</Box>
              <MuiLink href={site.url} target="_blank" variant="body2" sx={{ display: 'flex', alignItems: 'center', gap: 0.5, color: 'primary.main' }}>
                <LinkIcon sx={{ fontSize: 13 }} />{site.url}
              </MuiLink>
            </Stack>
          </Box>
          <Stack direction="row" spacing={1.5}>
            <Button variant="outlined" startIcon={<Edit />} size="small" onClick={() => navigate(`/sites/${id}/edit`)} sx={{ borderRadius: 2, fontWeight: 600 }}>Edit Site</Button>
            <Button
              variant="contained" startIcon={checkingHealth ? <CircularProgress size={14} color="inherit" /> : <Refresh />}
              size="small" onClick={handleRunHealthCheck} disabled={checkingHealth}
              sx={{ borderRadius: 2, fontWeight: 600 }}
            >
              {checkingHealth ? 'Checking…' : 'Run Health Check'}
            </Button>
          </Stack>
        </Box>

        {/* ── Status Cards Row ── */}
        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} sx={{ mb: 3 }}>
          <StatusCard sx={{ bgcolor: `${hm.color}08`, border: `1px solid ${hm.color}30` }}>
            <HealthBadge status={site.overall_health} large />
          </StatusCard>

          <StatusCard>
            <Typography variant="caption" color="text.secondary">PHP Version</Typography>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: 0.5 }}>
              <Code sx={{ fontSize: 18, color: '#5C4A8A' }} />
              <Typography variant="h5" fontWeight={700}>{site.php_version ?? '—'}</Typography>
            </Box>
          </StatusCard>

          <StatusCard>
            <Typography variant="caption" color="text.secondary">WordPress</Typography>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: 0.5 }}>
              <Language sx={{ fontSize: 18, color: '#5C4A8A' }} />
              <Typography variant="h5" fontWeight={700}>{site.wp_version ?? '—'}</Typography>
            </Box>
          </StatusCard>

          <StatusCard>
            <Typography variant="caption" color="text.secondary">Last Checked</Typography>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: 0.5 }}>
              <AccessTime sx={{ fontSize: 18, color: '#5C4A8A' }} />
              <Typography variant="body1" fontWeight={600}>
                {site.last_checked_at ? formatDistanceToNow(new Date(site.last_checked_at), { addSuffix: true }) : 'Never'}
              </Typography>
            </Box>
          </StatusCard>
        </Stack>

        {/* ── Tabs ── */}
        <Box sx={{ borderBottom: '1px solid #DDD4F8', mb: 0 }}>
          <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ '& .MuiTab-root': { fontWeight: 600, fontSize: '0.875rem', minHeight: 44 } }}>
            <Tab icon={<GridView sx={{ fontSize: 16 }} />} iconPosition="start" label="Overview" />
            <Tab
              icon={<GridView sx={{ fontSize: 16 }} />}
              iconPosition="start"
              label={
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.8 }}>
                  Plugins
                  {updateCount > 0 && <Box sx={{ bgcolor: '#8E43F0', color: '#fff', borderRadius: 8, px: 0.8, fontSize: '0.7rem', fontWeight: 700, lineHeight: '18px' }}>{updateCount}</Box>}
                </Box>
              }
            />
            <Tab
              icon={<GridView sx={{ fontSize: 16 }} />}
              iconPosition="start"
              label={
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.8 }}>
                  Themes
                  {themeUpdateCount > 0 && <Box sx={{ bgcolor: '#D97706', color: '#fff', borderRadius: 8, px: 0.8, fontSize: '0.7rem', fontWeight: 700, lineHeight: '18px' }}>{themeUpdateCount}</Box>}
                </Box>
              }
            />
            <Tab icon={<SecurityIcon sx={{ fontSize: 16 }} />} iconPosition="start" label="Security" />
            <Tab icon={<Speed sx={{ fontSize: 16 }} />} iconPosition="start" label="Lighthouse" />
            <Tab icon={<Analytics sx={{ fontSize: 16 }} />} iconPosition="start" label="SEO" />
            <Tab icon={<HistoryIcon sx={{ fontSize: 16 }} />} iconPosition="start" label="Check History" />
            <Tab icon={<ListIcon sx={{ fontSize: 16 }} />} iconPosition="start" label="Work Items" />
          </Tabs>
        </Box>

        {/* ── Tab Panels ── */}
        {/* Overview */}
        <TabPanel value={tab} index={0}>
          <Grid container spacing={3}>
            <Grid item xs={12} lg={8}>
              {/* Site Information */}
              <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none', mb: 2.5 }}>
                <CardContent sx={{ p: 3 }}>
                  <Typography variant="h6" fontWeight={700} sx={{ mb: 2 }}>Site Information</Typography>
                  <SiteInfoGrid
                site={site}
                coreUpdateVersion={coreUpdateVersion}
                onUpdateCore={handleUpdateCore}
                updatingCore={updatingCore}
              />
                </CardContent>
              </Card>

              {/* Active Theme */}
              <ActiveThemeCard
                theme={themeData}
                siteId={site.id}
                confirm={confirm}
                notify={notify}
              />
            </Grid>

            {/* Right sidebar panel */}
            <Grid item xs={12} lg={4}>
              <SiteRightPanel site={site} plugins={plugins} notify={notify} />
            </Grid>
          </Grid>
        </TabPanel>

        {/* Plugins */}
        <TabPanel value={tab} index={1}>
          <PluginsList initialPlugins={plugins} siteId={site.id} confirm={confirm} notify={notify} />
        </TabPanel>

        {/* Themes */}
        <TabPanel value={tab} index={2}>
          <ThemesList initialThemes={themes} siteId={site.id} confirm={confirm} notify={notify} />
        </TabPanel>

        {/* Security */}
        <TabPanel value={tab} index={3}>
          <SecurityTab siteId={site.id} issues={site?.security_issues ?? []} />
        </TabPanel>

        {/* Lighthouse */}
        <TabPanel value={tab} index={4}>
          <LighthouseTab siteId={site.id} />
        </TabPanel>

        {/* SEO */}
        <TabPanel value={tab} index={5}>
          <SeoTab siteId={site.id} />
        </TabPanel>

        {/* Check History */}
        <TabPanel value={tab} index={6}>
          <CheckHistoryTab history={site?.check_history ?? []} />
        </TabPanel>

        {/* Work Items */}
        <TabPanel value={tab} index={7}>
          <WorkItemsTab items={site?.work_items ?? []} />
        </TabPanel>
      </Box>
    </AppLayout>
  );
}
