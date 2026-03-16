import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Box, Grid, Card, CardContent, Typography, Button, Stack, Avatar,
  TextField, IconButton, Tooltip, Skeleton, Alert, Chip, Divider,
  LinearProgress, Tabs, Tab,
} from '@mui/material';
import {
  ArrowBack, Edit, Check, Close, Email, Phone, Business, FolderOpen,
  AttachMoney, Assignment,
} from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import StatusBadge from '../../components/saas/StatusBadge';
import { GET_CLIENT } from '../../graphql/queries';
import { UPDATE_CLIENT } from '../../graphql/mutations';
import { fmtDate } from '../../utils/dates';

function TabPanel({ value, index, children }) {
  return value === index ? <Box sx={{ pt: 2.5 }}>{children}</Box> : null;
}

export default function ClientDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { enqueueSnackbar } = useSnackbar();
  const [tab, setTab] = useState(0);
  const [editSection, setEditSection] = useState(null);
  const [sectionForm, setSectionForm] = useState({});
  const setSF = (k, v) => setSectionForm((p) => ({ ...p, [k]: v }));

  const { data, loading, error, refetch } = useQuery(GET_CLIENT, { variables: { id }, fetchPolicy: 'cache-and-network' });
  const [updateClient, { loading: saving }] = useMutation(UPDATE_CLIENT, {
    onCompleted: () => { refetch(); setEditSection(null); enqueueSnackbar('Client updated', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to update', { variant: 'error' }),
  });

  const startEdit = (section, fields) => { setEditSection(section); setSectionForm(fields); };
  const cancelEdit = () => { setEditSection(null); setSectionForm({}); };
  const saveSection = () => updateClient({ variables: { id, ...sectionForm } });

  if (loading && !data) return (
    <Box>{[1, 2, 3].map((i) => <Skeleton key={i} variant="rounded" height={120} sx={{ mb: 2, borderRadius: 3 }} />)}</Box>
  );
  if (error || !data?.client) return (
    <Alert severity="error">Client not found. <Button onClick={() => navigate('/clients')}>Back to Clients</Button></Alert>
  );

  const client = data.client;
  const initials = client.name.split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();
  const projects = client.projects ?? [];

  return (
    <Box sx={{ pb: { xs: 10, md: 0 } }}>
      <Button startIcon={<ArrowBack />} onClick={() => navigate('/clients')} size="small" sx={{ color: 'text.secondary', mb: 2 }}>
        All Clients
      </Button>

      {/* ── Hero header ── */}
      <Card elevation={0} sx={{
        borderRadius: 3, mb: 3, overflow: 'hidden',
        background: 'linear-gradient(135deg, #1A0A3C 0%, #3D1A80 50%, #8E43F0 100%)',
        border: '1px solid #DDD4F8',
      }}>
        <CardContent sx={{ p: { xs: 2.5, sm: 3.5 } }}>
          <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 2 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2.5 }}>
              {/* Large gradient-ring avatar */}
              <Box sx={{ p: '3px', borderRadius: '50%', background: 'linear-gradient(135deg,#C4B0F0,#fff)', flexShrink: 0 }}>
                <Avatar sx={{ width: 64, height: 64, bgcolor: '#6A1FCC', fontSize: '1.4rem', fontWeight: 800, border: '3px solid rgba(255,255,255,0.2)' }}>
                  {initials}
                </Avatar>
              </Box>
              <Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 0.5 }}>
                  <Typography variant="h4" fontWeight={800} sx={{ color: '#fff', letterSpacing: '-0.02em' }}>{client.name}</Typography>
                  <StatusBadge value={client.status} />
                </Box>
                <Stack direction="row" spacing={1.5} flexWrap="wrap">
                  {client.company && (
                    <Chip icon={<Business sx={{ fontSize: '12px!important' }} />} label={client.company} size="small"
                      sx={{ bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', fontSize: '0.72rem', '& .MuiChip-icon': { color: 'rgba(255,255,255,0.7)' } }} />
                  )}
                  <Chip label={`${projects.length} projects`} size="small"
                    sx={{ bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', fontSize: '0.72rem' }} />
                  <Chip label={`Client since ${fmtDate(client.joined_at)}`} size="small"
                    sx={{ bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', fontSize: '0.72rem' }} />
                </Stack>
              </Box>
            </Box>
            <Button variant="contained" startIcon={<Edit />} size="small"
              onClick={() => navigate(`/clients/${id}/edit`)}
              sx={{ bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', borderRadius: 2, boxShadow: 'none', '&:hover': { bgcolor: 'rgba(255,255,255,0.25)' } }}>
              Edit
            </Button>
          </Box>
        </CardContent>
      </Card>

      {/* ── Stat mini-cards ── */}
      <Grid container spacing={2} sx={{ mb: 3 }}>
        {[
          { icon: AttachMoney, label: 'Total Revenue', value: `£${Number(client.revenue ?? 0).toLocaleString()}`, color: '#16A34A', bg: '#F0FDF4' },
          { icon: FolderOpen, label: 'Projects', value: projects.length, color: '#8E43F0', bg: '#EDE8FC' },
          { icon: Email, label: 'Email', value: client.email, color: '#0099C2', bg: '#E0F5FB' },
          { icon: Phone, label: 'Phone', value: client.phone ?? '—', color: '#D97706', bg: '#FFFBEB' },
        ].map((s) => (
          <Grid item xs={6} lg={3} key={s.label}>
            <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8' }}>
              <CardContent sx={{ p: 2, display: 'flex', alignItems: 'center', gap: 1.5 }}>
                <Box sx={{ width: 36, height: 36, borderRadius: 2, bgcolor: s.bg, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                  <s.icon sx={{ fontSize: 18, color: s.color }} />
                </Box>
                <Box sx={{ minWidth: 0 }}>
                  <Typography variant="caption" color="text.secondary" fontWeight={600}>{s.label}</Typography>
                  <Typography variant="body2" fontWeight={700} noWrap>{s.value}</Typography>
                </Box>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      {/* ── Tabs ── */}
      <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8' }}>
        <Box sx={{ borderBottom: '1px solid #EDE8FC' }}>
          <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ px: 2, '& .MuiTab-root': { fontWeight: 600, fontSize: '0.84rem', minHeight: 48, textTransform: 'none' }, '& .MuiTabs-indicator': { height: 3, borderRadius: 2 } }}>
            <Tab icon={<Business sx={{ fontSize: 16 }} />} iconPosition="start" label="Contact Info" />
            <Tab icon={<FolderOpen sx={{ fontSize: 16 }} />} iconPosition="start" label={`Projects (${projects.length})`} />
          </Tabs>
        </Box>

        <CardContent sx={{ p: 3 }}>
          {/* Contact tab */}
          <TabPanel value={tab} index={0}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
              <Typography variant="h6" fontWeight={700}>Contact Information</Typography>
              {editSection === 'contact' ? (
                <Stack direction="row" spacing={0.5}>
                  <Tooltip title="Save"><IconButton size="small" color="success" onClick={saveSection} disabled={saving}><Check fontSize="small" /></IconButton></Tooltip>
                  <Tooltip title="Cancel"><IconButton size="small" onClick={cancelEdit}><Close fontSize="small" /></IconButton></Tooltip>
                </Stack>
              ) : (
                <Tooltip title="Edit section">
                  <IconButton size="small" onClick={() => startEdit('contact', { name: client.name, email: client.email, company: client.company ?? '', phone: client.phone ?? '' })}>
                    <Edit fontSize="small" />
                  </IconButton>
                </Tooltip>
              )}
            </Box>

            {editSection === 'contact' ? (
              <Grid container spacing={2}>
                {[
                  { label: 'Name', key: 'name' },
                  { label: 'Email', key: 'email' },
                  { label: 'Company', key: 'company' },
                  { label: 'Phone', key: 'phone' },
                ].map(({ label, key }) => (
                  <Grid item xs={12} sm={6} key={key}>
                    <TextField label={label} size="small" fullWidth value={sectionForm[key] ?? ''} onChange={(e) => setSF(key, e.target.value)} sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }} />
                  </Grid>
                ))}
              </Grid>
            ) : (
              <Grid container spacing={2}>
                {[
                  { icon: Email, label: 'Email', value: client.email },
                  { icon: Business, label: 'Company', value: client.company ?? '—' },
                  { icon: Phone, label: 'Phone', value: client.phone ?? '—' },
                ].map(({ icon: Icon, label, value }) => (
                  <Grid item xs={12} sm={6} key={label}>
                    <Box sx={{ p: 2, borderRadius: 2, bgcolor: '#F7F5FF', border: '1px solid #EDE8FC', display: 'flex', alignItems: 'center', gap: 1.5 }}>
                      <Icon sx={{ fontSize: 16, color: 'text.secondary', flexShrink: 0 }} />
                      <Box>
                        <Typography variant="caption" color="text.secondary" fontWeight={600}>{label}</Typography>
                        <Typography variant="body2" noWrap>{value}</Typography>
                      </Box>
                    </Box>
                  </Grid>
                ))}
              </Grid>
            )}
          </TabPanel>

          {/* Projects tab */}
          <TabPanel value={tab} index={1}>
            {projects.length === 0 ? (
              <Box sx={{ py: 5, textAlign: 'center' }}>
                <Typography color="text.secondary">No projects for this client yet.</Typography>
                <Button size="small" variant="outlined" sx={{ mt: 2, borderRadius: 2 }} onClick={() => navigate('/projects/create')}>
                  Create a Project
                </Button>
              </Box>
            ) : (
              <Stack spacing={1.5}>
                {projects.map((p) => {
                  const isOverdue = p.due_date && new Date(p.due_date) < new Date();
                  return (
                    <Box key={p.id} sx={{
                      p: 2, border: '1px solid #DDD4F8', borderRadius: 2.5, cursor: 'pointer',
                      transition: 'all 0.15s',
                      '&:hover': { bgcolor: '#F7F5FF', borderColor: '#C4B0F0', transform: 'translateX(3px)' },
                    }}
                      onClick={() => navigate(`/projects/${p.id}`)}>
                      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 0.75 }}>
                        <Typography variant="body2" fontWeight={700}>{p.name}</Typography>
                        <Stack direction="row" spacing={1} alignItems="center">
                          <Typography variant="body2" fontWeight={600} color="primary.main">£{Number(p.budget ?? 0).toLocaleString()}</Typography>
                          <StatusBadge value={p.status} />
                        </Stack>
                      </Box>
                      <Typography variant="caption" sx={{ color: isOverdue ? 'error.main' : 'text.secondary' }}>
                        {p.developers?.[0]?.name ?? 'Unassigned'} · Due {fmtDate(p.due_date)} {isOverdue && '⚠'}
                      </Typography>
                      <LinearProgress variant="determinate" value={p.progress ?? 0}
                        sx={{ mt: 1, height: 4, borderRadius: 2, bgcolor: '#DDD4F8', '& .MuiLinearProgress-bar': { bgcolor: '#8E43F0', borderRadius: 2 } }} />
                    </Box>
                  );
                })}
              </Stack>
            )}
          </TabPanel>
        </CardContent>
      </Card>
    </Box>
  );
}
