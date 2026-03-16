import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Box, Grid, Card, CardContent, Typography, Button, Chip, Stack, Avatar,
  LinearProgress, Skeleton, Alert, Tabs, Tab, Divider,
} from '@mui/material';
import {
  ArrowBack, Edit, Email, Code, Business, Assignment, FolderOpen,
  CheckCircleOutline, RadioButtonUnchecked,
} from '@mui/icons-material';
import { useQuery } from '@apollo/client';
import StatusBadge from '../../components/saas/StatusBadge';
import { GET_DEVELOPER } from '../../graphql/queries';
import { fmtDate } from '../../utils/dates';
import { useAuth } from '../../contexts/AuthContext';

const priorityColor = { Critical: '#DC2626', High: '#D97706', Medium: '#8E43F0', Low: '#16A34A' };

function TabPanel({ value, index, children }) {
  return value === index ? <Box sx={{ pt: 2.5 }}>{children}</Box> : null;
}

export default function DeveloperDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { hasRole } = useAuth();
  const [tab, setTab] = useState(0);

  const { data, loading: loadingDev, error: devError } = useQuery(GET_DEVELOPER, {
    variables: { id }, skip: !id, fetchPolicy: 'cache-and-network',
  });

  const dev = data?.developer;
  const tasks = dev?.tasks ?? [];
  const devProjects = dev?.projects ?? [];

  const skills = dev?.bio ? dev.bio.split(',').map((s) => s.trim()).filter(Boolean) : [];
  const initials = (dev?.name ?? '??').split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();

  const taskStats = [
    { label: 'Total', value: tasks.length, color: '#8E43F0', bg: '#EDE8FC' },
    { label: 'In Progress', value: tasks.filter((t) => t.status === 'in_progress').length, color: '#D97706', bg: '#FFFBEB' },
    { label: 'Completed', value: tasks.filter((t) => t.status === 'done').length, color: '#16A34A', bg: '#F0FDF4' },
    { label: 'Open', value: tasks.filter((t) => t.status === 'open').length, color: '#0099C2', bg: '#E0F5FB' },
  ];

  if (loadingDev && !data) return (
    <Box>{[1, 2, 3].map((i) => <Skeleton key={i} variant="rounded" height={120} sx={{ mb: 2, borderRadius: 3 }} />)}</Box>
  );
  if (devError || (!loadingDev && !dev)) return (
    <Alert severity="error">Developer not found. <Button onClick={() => navigate('/developers')}>Back</Button></Alert>
  );

  return (
    <Box sx={{ pb: { xs: 10, md: 0 } }}>
      <Button startIcon={<ArrowBack />} onClick={() => navigate('/developers')} size="small" sx={{ color: 'text.secondary', mb: 2 }}>
        All Developers
      </Button>

      {/* ── Hero header ── */}
      <Card elevation={0} sx={{
        borderRadius: 3, mb: 3, overflow: 'hidden',
        background: 'linear-gradient(135deg, #0F0028 0%, #2D0A6A 50%, #6A1FCC 100%)',
        border: '1px solid #DDD4F8',
      }}>
        <CardContent sx={{ p: { xs: 2.5, sm: 3.5 } }}>
          <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 2 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2.5 }}>
              {/* Large gradient-ring avatar */}
              <Box sx={{ p: '3px', borderRadius: '50%', background: 'linear-gradient(135deg,#C4B0F0,#D4006A)', flexShrink: 0 }}>
                <Avatar sx={{ width: 64, height: 64, bgcolor: '#8E43F0', fontSize: '1.4rem', fontWeight: 800, border: '3px solid rgba(255,255,255,0.15)' }}>
                  {initials}
                </Avatar>
              </Box>
              <Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 0.5 }}>
                  <Typography variant="h4" fontWeight={800} sx={{ color: '#fff', letterSpacing: '-0.02em' }}>{dev.name}</Typography>
                  <StatusBadge value={dev.status} />
                </Box>
                <Stack direction="row" spacing={1} flexWrap="wrap">
                  {dev.company && (
                    <Chip icon={<Business sx={{ fontSize: '12px!important' }} />} label={dev.company} size="small"
                      sx={{ bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', fontSize: '0.72rem', '& .MuiChip-icon': { color: 'rgba(255,255,255,0.7)' } }} />
                  )}
                  <Chip icon={<Assignment sx={{ fontSize: '12px!important' }} />} label={`${tasks.length} tasks`} size="small"
                    sx={{ bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', fontSize: '0.72rem', '& .MuiChip-icon': { color: 'rgba(255,255,255,0.7)' } }} />
                  <Chip icon={<FolderOpen sx={{ fontSize: '12px!important' }} />} label={`${devProjects.length} projects`} size="small"
                    sx={{ bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', fontSize: '0.72rem', '& .MuiChip-icon': { color: 'rgba(255,255,255,0.7)' } }} />
                </Stack>
              </Box>
            </Box>
            {hasRole('SuperAdmin') && (
              <Button variant="contained" startIcon={<Edit />} size="small" onClick={() => navigate(`/developers/${id}/edit`)}
                sx={{ bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', borderRadius: 2, boxShadow: 'none', '&:hover': { bgcolor: 'rgba(255,255,255,0.25)' } }}>
                Edit
              </Button>
            )}
          </Box>
        </CardContent>
      </Card>

      {/* ── Stat mini-cards ── */}
      <Grid container spacing={2} sx={{ mb: 3 }}>
        {taskStats.map((s) => (
          <Grid item xs={6} sm={3} key={s.label}>
            <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8', textAlign: 'center' }}>
              <CardContent sx={{ py: 2 }}>
                <Typography variant="h4" fontWeight={800} sx={{ color: s.color, letterSpacing: '-0.02em' }}>{s.value}</Typography>
                <Typography variant="caption" color="text.secondary" fontWeight={600}>{s.label}</Typography>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      {/* ── Tabs ── */}
      <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8' }}>
        <Box sx={{ borderBottom: '1px solid #EDE8FC' }}>
          <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ px: 2, '& .MuiTab-root': { fontWeight: 600, fontSize: '0.84rem', minHeight: 48, textTransform: 'none' }, '& .MuiTabs-indicator': { height: 3, borderRadius: 2 } }}>
            <Tab icon={<Business sx={{ fontSize: 16 }} />} iconPosition="start" label="Profile" />
            <Tab icon={<FolderOpen sx={{ fontSize: 16 }} />} iconPosition="start" label={`Projects (${devProjects.length})`} />
            <Tab icon={<Assignment sx={{ fontSize: 16 }} />} iconPosition="start" label={`Tasks (${tasks.length})`} />
          </Tabs>
        </Box>

        <CardContent sx={{ p: 3 }}>
          {/* Profile tab */}
          <TabPanel value={tab} index={0}>
            <Stack spacing={2}>
              {[
                { icon: Email, label: 'Email', value: dev.email },
                { icon: Business, label: 'Company', value: dev.company ?? '—' },
              ].map(({ icon: Icon, label, value }) => (
                <Box key={label} sx={{ display: 'flex', alignItems: 'center', gap: 1.5, p: 2, borderRadius: 2, bgcolor: '#F7F5FF', border: '1px solid #EDE8FC' }}>
                  <Icon sx={{ fontSize: 16, color: 'text.secondary' }} />
                  <Box>
                    <Typography variant="caption" color="text.secondary" fontWeight={600}>{label}</Typography>
                    <Typography variant="body2">{value}</Typography>
                  </Box>
                </Box>
              ))}

              {/* Skills */}
              <Box>
                <Typography variant="body2" fontWeight={700} sx={{ mb: 1.5 }}>Skills</Typography>
                {skills.length ? (
                  <Stack direction="row" flexWrap="wrap" gap={1}>
                    {skills.map((s) => (
                      <Chip key={s} label={s} size="small" icon={<Code sx={{ fontSize: '14px!important' }} />}
                        sx={{ bgcolor: '#EDE8FC', color: '#5B21B6', fontWeight: 600 }} />
                    ))}
                  </Stack>
                ) : (
                  <Typography variant="body2" color="text.secondary">No skills listed.</Typography>
                )}
              </Box>
            </Stack>
          </TabPanel>

          {/* Projects tab */}
          <TabPanel value={tab} index={1}>
            {loadingDev ? [1, 2].map((i) => <Skeleton key={i} height={52} sx={{ mb: 1, borderRadius: 2 }} />) :
              devProjects.length === 0 ? (
                <Box sx={{ py: 4, textAlign: 'center' }}>
                  <Typography color="text.secondary">No projects assigned yet.</Typography>
                </Box>
              ) : devProjects.map((p) => (
                <Box key={p.id} sx={{ mb: 1.5, p: 2, border: '1px solid #DDD4F8', borderRadius: 2, cursor: 'pointer', transition: 'all 0.15s', '&:hover': { bgcolor: '#F7F5FF', borderColor: '#C4B0F0', transform: 'translateX(3px)' } }}
                  onClick={() => navigate(`/projects/${p.id}`)}>
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.75 }}>
                    <Typography variant="body2" fontWeight={700}>{p.name}</Typography>
                    {p.status && <StatusBadge value={p.status} />}
                  </Box>
                  <LinearProgress variant={p.progress != null ? 'determinate' : 'indeterminate'} value={p.progress ?? 0}
                    sx={{ height: 4, borderRadius: 4, bgcolor: '#DDD4F8', '& .MuiLinearProgress-bar': { bgcolor: '#8E43F0', borderRadius: 4 } }} />
                </Box>
              ))
            }
          </TabPanel>

          {/* Tasks tab */}
          <TabPanel value={tab} index={2}>
            {loadingDev ? [1, 2, 3].map((i) => <Skeleton key={i} height={56} sx={{ mb: 1, borderRadius: 2 }} />) :
              tasks.length === 0 ? (
                <Box sx={{ py: 4, textAlign: 'center' }}>
                  <Typography color="text.secondary">No tasks assigned.</Typography>
                </Box>
              ) : (
                <Stack spacing={1.5}>
                  {tasks.slice(0, 10).map((t) => {
                    const isDone = t.status === 'done';
                    const pColor = priorityColor[t.priority] ?? '#8E43F0';
                    const isOverdue = t.due_date && new Date(t.due_date) < new Date();
                    return (
                      <Box key={t.id} sx={{
                        display: 'flex', alignItems: 'center', gap: 1.5, p: 1.5,
                        border: '1px solid #DDD4F8', borderRadius: 2,
                        borderLeft: `3px solid ${pColor}`,
                        opacity: isDone ? 0.65 : 1,
                        transition: 'all 0.12s',
                        '&:hover': { bgcolor: '#F7F5FF' },
                      }}>
                        {isDone
                          ? <CheckCircleOutline sx={{ fontSize: 18, color: '#16A34A', flexShrink: 0 }} />
                          : <RadioButtonUnchecked sx={{ fontSize: 18, color: pColor, flexShrink: 0 }} />}
                        <Box sx={{ flex: 1, minWidth: 0 }}>
                          <Typography variant="body2" fontWeight={isDone ? 400 : 600} noWrap sx={{ textDecoration: isDone ? 'line-through' : 'none' }}>{t.title}</Typography>
                          <Typography variant="caption" sx={{ color: isOverdue && !isDone ? 'error.main' : 'text.secondary' }}>
                            {t.project?.name ?? '—'} · {fmtDate(t.due_date)}
                          </Typography>
                        </Box>
                        <StatusBadge value={t.status} />
                      </Box>
                    );
                  })}
                </Stack>
              )
            }
          </TabPanel>
        </CardContent>
      </Card>
    </Box>
  );
}
