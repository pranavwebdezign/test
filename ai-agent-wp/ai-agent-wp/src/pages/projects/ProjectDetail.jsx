import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Box, Grid, Card, CardContent, Typography, Button, Chip, Stack,
  LinearProgress, Skeleton, Alert, Avatar, AvatarGroup, Tabs, Tab,
  CircularProgress, Divider, Tooltip,
} from '@mui/material';
import {
  ArrowBack, Edit, FolderOpen, CalendarToday, AttachMoney, Code,
  CheckCircle, RadioButtonUnchecked, People, Assignment, Language,
} from '@mui/icons-material';
import { useQuery } from '@apollo/client';
import { GET_PROJECT } from '../../graphql/queries';
import { fmtDate } from '../../utils/dates';
import StatusBadge from '../../components/saas/StatusBadge';
import EmptyState from '../../components/common/EmptyState';
import { useAuth } from '../../contexts/AuthContext';

const priorityColor = { Critical: '#DC2626', High: '#D97706', Medium: '#8E43F0', Low: '#16A34A' };
const priorityBg = { Critical: '#FFF1F2', High: '#FFFBEB', Medium: '#EDE8FC', Low: '#F0FDF4' };

function DetailSkeleton() {
  return (
    <Box>
      <Skeleton variant="text" width={120} height={36} sx={{ mb: 2 }} />
      <Skeleton variant="rounded" height={160} sx={{ borderRadius: 3, mb: 3 }} />
      <Grid container spacing={2.5}>
        {[1, 2, 3, 4].map((i) => (
          <Grid item xs={6} lg={3} key={i}>
            <Skeleton variant="rounded" height={90} sx={{ borderRadius: 3 }} />
          </Grid>
        ))}
        <Grid item xs={12}><Skeleton variant="rounded" height={200} sx={{ borderRadius: 3 }} /></Grid>
      </Grid>
    </Box>
  );
}

function TabPanel({ value, index, children }) {
  return value === index ? (
    <Box sx={{ pt: 2.5 }}>{children}</Box>
  ) : null;
}

export default function ProjectDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { hasRole } = useAuth();
  const [tab, setTab] = useState(0);

  const { data, loading, error } = useQuery(GET_PROJECT, {
    variables: { id },
    fetchPolicy: 'cache-and-network',
    skip: !id,
  });

  if (loading && !data) return <DetailSkeleton />;

  if (error) {
    return (
      <Box>
        <Button startIcon={<ArrowBack />} onClick={() => navigate('/projects')} size="small" sx={{ mb: 2, color: 'text.secondary' }}>
          All Projects
        </Button>
        <Alert severity="error" sx={{ borderRadius: 2 }}>Failed to load project details. Please try again.</Alert>
      </Box>
    );
  }

  const project = data?.project;
  if (!project) {
    return (
      <Box>
        <EmptyState type="project" message="Project not found" actionLabel="Back to Projects" onAction={() => navigate('/projects')} />
      </Box>
    );
  }

  const projectTasks = project.tasks ?? [];
  const developers = project.developers ?? [];
  const doneTasks = projectTasks.filter((t) => t.status === 'done').length;
  const today = new Date();

  return (
    <Box sx={{ pb: { xs: 18, md: 0 } }}>
      {/* Back button */}
      <Button startIcon={<ArrowBack />} onClick={() => navigate('/projects')} size="small" sx={{ color: 'text.secondary', mb: 2 }}>
        All Projects
      </Button>

      {/* ── Hero header card ─────────────────────────────── */}
      <Card elevation={0} sx={{
        borderRadius: 3, mb: 3, overflow: 'hidden',
        border: '1px solid #DDD4F8',
        background: 'linear-gradient(135deg, #1A0A3C 0%, #3D1A80 50%, #8E43F0 100%)',
      }}>
        <CardContent sx={{ p: { xs: 2.5, sm: 3.5 } }}>
          <Box sx={{
            display: 'flex', alignItems: { xs: 'center', sm: 'flex-start' },
            justifyContent: 'space-between',
            flexDirection: { xs: 'column', sm: 'row' },
            textAlign: { xs: 'center', sm: 'left' },
            flexWrap: 'wrap', gap: 2,
          }}>
            {/* Left */}
            <Box sx={{ flex: 1, minWidth: 0 }}>
              {/* Icon + name */}
              <Stack direction="row" alignItems="center" justifyContent={{ xs: 'center', sm: 'flex-start' }} spacing={1.5} sx={{ mb: 1 }}>
                <Box sx={{ width: 44, height: 44, borderRadius: 2, bgcolor: 'rgba(255,255,255,0.15)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                  <FolderOpen sx={{ color: '#fff', fontSize: 22 }} />
                </Box>
                <Box sx={{ minWidth: 0 }}>
                  <Typography variant="h5" fontWeight={800} sx={{ color: '#fff', lineHeight: 1.2 }}>
                    {project.name}
                  </Typography>
                  <Typography variant="caption" sx={{ color: 'rgba(255,255,255,0.7)' }}>
                    {project.client?.name ?? '—'}
                  </Typography>
                </Box>
              </Stack>

              {project.description && (
                <Typography variant="body2" sx={{ color: 'rgba(255,255,255,0.75)', mt: 0.5, mb: 1.5, maxWidth: 560 }}>
                  {project.description}
                </Typography>
              )}

              <Stack direction="row" spacing={1} flexWrap="wrap" justifyContent={{ xs: 'center', sm: 'flex-start' }}>
                <StatusBadge value={project.status} />
                {project.due_date && (
                  <Chip
                    icon={<CalendarToday sx={{ fontSize: 12 }} />}
                    label={fmtDate(project.due_date)}
                    size="small"
                    sx={{
                      bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', fontWeight: 600, fontSize: '0.72rem',
                      '& .MuiChip-icon': { color: 'rgba(255,255,255,0.8)' },
                    }}
                  />
                )}
                <Chip
                  label={`${doneTasks}/${projectTasks.length} tasks done`}
                  size="small"
                  sx={{ bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', fontWeight: 600, fontSize: '0.72rem' }}
                />
                {project.is_wordpress && (
                  <Chip
                    icon={<Language sx={{ fontSize: 13 }} />}
                    label={project.wp_site ? 'WordPress Site' : 'WordPress'}
                    size="small"
                    clickable
                    onClick={() => navigate('/sites')}
                    sx={{
                      bgcolor: 'rgba(142,67,240,0.35)', color: '#fff', fontWeight: 700, fontSize: '0.72rem',
                      border: '1px solid rgba(255,255,255,0.3)',
                      '& .MuiChip-icon': { color: '#fff' },
                      '&:hover': { bgcolor: 'rgba(142,67,240,0.5)' },
                    }}
                  />
                )}
              </Stack>
            </Box>

            {/* Right: progress ring + edit — hidden on xs (edit moves to sticky bar) */}
            <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 1.5, flexShrink: 0 }}>
              {/* Circular progress ring */}
              <Box sx={{ position: 'relative', display: 'inline-flex' }}>
                <CircularProgress variant="determinate" value={100} size={72} thickness={4} sx={{ color: 'rgba(255,255,255,0.2)', position: 'absolute' }} />
                <CircularProgress variant="determinate" value={project.progress ?? 0} size={72} thickness={4} sx={{ color: project.progress >= 100 ? '#22C55E' : '#C4B0F0' }} />
                <Box sx={{ position: 'absolute', inset: 0, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}>
                  <Typography variant="caption" fontWeight={800} sx={{ color: '#fff', fontSize: '0.85rem', lineHeight: 1 }}>{project.progress ?? 0}%</Typography>
                  <Typography sx={{ color: 'rgba(255,255,255,0.6)', fontSize: '0.55rem' }}>done</Typography>
                </Box>
              </Box>

              {hasRole('SuperAdmin') && (
                <Button
                  variant="contained" startIcon={<Edit />} size="small"
                  onClick={() => navigate(`/projects/${id}/edit`)}
                  sx={{ bgcolor: 'rgba(255,255,255,0.15)', color: '#fff', borderRadius: 2, boxShadow: 'none', '&:hover': { bgcolor: 'rgba(255,255,255,0.25)' }, fontSize: '0.78rem', display: { xs: 'none', sm: 'flex' } }}
                >
                  Edit
                </Button>
              )}
            </Box>
          </Box>

          {/* Developer avatars at bottom */}
          {developers.length > 0 && (
            <Box sx={{ mt: 2.5, pt: 2, borderTop: '1px solid rgba(255,255,255,0.12)', display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <Typography variant="caption" sx={{ color: 'rgba(255,255,255,0.6)', fontWeight: 600 }}>Team:</Typography>
              <AvatarGroup max={6} sx={{ '& .MuiAvatar-root': { width: 28, height: 28, fontSize: '0.65rem', border: '2px solid rgba(255,255,255,0.3)', bgcolor: 'primary.dark' } }}>
                {developers.map((d) => (
                  <Tooltip key={d.id} title={d.name}>
                    <Avatar>{d.name.split(' ').map((n) => n[0]).join('').slice(0, 2)}</Avatar>
                  </Tooltip>
                ))}
              </AvatarGroup>
            </Box>
          )}
        </CardContent>
      </Card>

      {/* ── Stat mini-cards ─────────────────────────────── */}
      <Grid container spacing={2} sx={{ mb: 3 }}>
        {[
          { icon: AttachMoney, label: 'Budget', value: project.budget ? `£${Number(project.budget).toLocaleString()}` : '—', color: '#8E43F0', bg: '#EDE8FC' },
          { icon: CalendarToday, label: 'Due Date', value: fmtDate(project.due_date), color: '#D97706', bg: '#FFFBEB' },
          { icon: Assignment, label: 'Tasks', value: `${projectTasks.length} total`, color: '#0099C2', bg: '#E0F5FB' },
          { icon: People, label: 'Team', value: `${developers.length} developers`, color: '#16A34A', bg: '#F0FDF4' },
        ].map((s) => (
          <Grid item xs={6} lg={3} key={s.label}>
            <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8' }}>
              <CardContent sx={{ p: 2, display: 'flex', alignItems: 'center', gap: 1.5 }}>
                <Box sx={{ width: 38, height: 38, borderRadius: 2, bgcolor: s.bg, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                  <s.icon sx={{ fontSize: 18, color: s.color }} />
                </Box>
                <Box sx={{ minWidth: 0 }}>
                  <Typography variant="caption" color="text.secondary" fontWeight={600} noWrap>{s.label}</Typography>
                  <Typography variant="body1" fontWeight={700} noWrap>{s.value}</Typography>
                </Box>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      {/* ── Tabs ────────────────────────────────────────── */}
      <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8' }}>
        <Box sx={{ borderBottom: '1px solid #EDE8FC' }}>
          <Tabs
            value={tab}
            onChange={(_, v) => setTab(v)}
            sx={{
              px: 2,
              '& .MuiTab-root': { fontWeight: 600, fontSize: '0.84rem', minHeight: 48, textTransform: 'none' },
              '& .MuiTabs-indicator': { height: 3, borderRadius: 2 },
            }}
          >
            <Tab icon={<FolderOpen sx={{ fontSize: 16 }} />} iconPosition="start" label={<Box component="span" sx={{ display: { xs: 'none', sm: 'inline' } }}>Overview</Box>} />
            <Tab icon={<Assignment sx={{ fontSize: 16 }} />} iconPosition="start" label={<Box component="span" sx={{ display: { xs: 'none', sm: 'inline' } }}>{`Tasks (${projectTasks.length})`}</Box>} />
            <Tab icon={<People sx={{ fontSize: 16 }} />} iconPosition="start" label={<Box component="span" sx={{ display: { xs: 'none', sm: 'inline' } }}>{`Team (${developers.length})`}</Box>} />
          </Tabs>
        </Box>

        <CardContent sx={{ p: 3 }}>
          {/* ── Overview tab ── */}
          <TabPanel value={tab} index={0}>
            <Grid container spacing={2.5}>
              {/* Progress bar */}
              <Grid item xs={12}>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                  <Typography variant="body2" fontWeight={600}>Overall Progress</Typography>
                  <Typography variant="body2" fontWeight={700} color={project.progress === 100 ? 'success.main' : 'primary.main'}>
                    {project.progress ?? 0}%
                  </Typography>
                </Box>
                <LinearProgress
                  variant="determinate" value={project.progress ?? 0}
                  sx={{
                    height: 10, borderRadius: 5, bgcolor: '#DDD4F8',
                    '& .MuiLinearProgress-bar': { borderRadius: 5, background: project.progress === 100 ? 'linear-gradient(90deg,#16A34A,#22C55E)' : 'linear-gradient(90deg,#8E43F0,#D4006A)' },
                  }}
                />
              </Grid>

              {/* Info pairs */}
              {[
                { label: 'Client', value: project.client?.name ?? '—' },
                { label: 'Budget', value: project.budget ? `£${Number(project.budget).toLocaleString()}` : '—' },
                { label: 'Due Date', value: fmtDate(project.due_date) },
                { label: 'Status', value: <StatusBadge value={project.status} /> },
              ].map(({ label, value }) => (
                <Grid item xs={12} sm={6} key={label}>
                  <Box sx={{ p: 2, borderRadius: 2, bgcolor: '#F7F5FF', border: '1px solid #EDE8FC' }}>
                    <Typography variant="caption" color="text.secondary" fontWeight={600}>{label}</Typography>
                    <Box sx={{ mt: 0.5 }}>
                      {typeof value === 'string'
                        ? <Typography variant="body2" fontWeight={600}>{value}</Typography>
                        : value}
                    </Box>
                  </Box>
                </Grid>
              ))}

              {project.description && (
                <Grid item xs={12}>
                  <Box sx={{ p: 2, borderRadius: 2, bgcolor: '#F7F5FF', border: '1px solid #EDE8FC' }}>
                    <Typography variant="caption" color="text.secondary" fontWeight={600}>Description</Typography>
                    <Typography variant="body2" sx={{ mt: 0.5 }}>{project.description}</Typography>
                  </Box>
                </Grid>
              )}
            </Grid>
          </TabPanel>

          {/* ── Tasks tab ── */}
          <TabPanel value={tab} index={1}>
            {projectTasks.length === 0 ? (
              <Box sx={{ py: 5, textAlign: 'center' }}>
                <Typography color="text.secondary">No tasks for this project yet.</Typography>
              </Box>
            ) : (
              <Stack spacing={1.5}>
                {projectTasks.map((t) => {
                  const isOverdue = t.due_date && new Date(t.due_date) < today;
                  const isDone = t.status === 'done';
                  const pColor = priorityColor[t.priority] ?? '#8E43F0';
                  return (
                    <Box key={t.id} sx={{
                      display: 'flex', alignItems: 'center', gap: 1.5,
                      p: 1.5, border: '1px solid #DDD4F8', borderRadius: 2,
                      borderLeft: `3px solid ${pColor}`,
                      bgcolor: isDone ? 'transparent' : `${pColor}06`,
                      opacity: isDone ? 0.65 : 1,
                      transition: 'all 0.12s',
                      '&:hover': { bgcolor: `${pColor}10` },
                    }}>
                      {isDone
                        ? <CheckCircle sx={{ fontSize: 18, color: '#16A34A', flexShrink: 0 }} />
                        : <RadioButtonUnchecked sx={{ fontSize: 18, color: pColor, flexShrink: 0 }} />}
                      <Box sx={{ flex: 1, minWidth: 0 }}>
                        <Typography variant="body2" fontWeight={isDone ? 400 : 600} noWrap sx={{ textDecoration: isDone ? 'line-through' : 'none' }}>
                          {t.title}
                        </Typography>
                        <Typography variant="caption" sx={{ color: isOverdue && !isDone ? 'error.main' : 'text.secondary' }}>
                          Due {fmtDate(t.due_date)} · {t.assignee?.name ?? 'Unassigned'}
                        </Typography>
                      </Box>
                      <Stack direction="row" spacing={0.5}>
                        <Chip label={t.priority} size="small" sx={{ bgcolor: priorityBg[t.priority], color: pColor, fontWeight: 700, height: 20, fontSize: '0.68rem' }} />
                        <StatusBadge value={t.status} />
                      </Stack>
                    </Box>
                  );
                })}
              </Stack>
            )}
          </TabPanel>

          {/* ── Team tab ── */}
          <TabPanel value={tab} index={2}>
            {developers.length === 0 ? (
              <Box sx={{ py: 5, textAlign: 'center' }}>
                <Typography color="text.secondary">No developers assigned yet.</Typography>
              </Box>
            ) : (
              <Grid container spacing={2}>
                {developers.map((dev) => (
                  <Grid item xs={12} sm={6} lg={4} key={dev.id}>
                    <Box sx={{
                      display: 'flex', alignItems: 'center', gap: 1.5,
                      p: 2, border: '1px solid #DDD4F8', borderRadius: 2.5,
                      transition: 'all 0.15s',
                      '&:hover': { bgcolor: '#F7F5FF', borderColor: '#C4B0F0', transform: 'translateX(3px)' },
                    }}>
                      <Box sx={{ p: '2px', borderRadius: '50%', background: 'linear-gradient(135deg,#8E43F0,#D4006A)', flexShrink: 0 }}>
                        <Avatar sx={{ width: 38, height: 38, bgcolor: '#6A1FCC', fontSize: '0.75rem', fontWeight: 700, border: '2px solid #fff' }}>
                          {dev.name.split(' ').map((n) => n[0]).join('').slice(0, 2)}
                        </Avatar>
                      </Box>
                      <Box sx={{ minWidth: 0 }}>
                        <Typography variant="body2" fontWeight={700} noWrap>{dev.name}</Typography>
                        <Typography variant="caption" color="text.secondary" noWrap>{dev.email}</Typography>
                      </Box>
                    </Box>
                  </Grid>
                ))}
              </Grid>
            )}
          </TabPanel>
        </CardContent>
      </Card>
      {/* Mobile sticky action bar — xs only */}
      {hasRole('SuperAdmin') && (
        <Box sx={{
          display: { xs: 'flex', sm: 'none' },
          position: 'fixed', bottom: 64, left: 0, right: 0, zIndex: 1200,
          px: 2, py: 1.5,
          bgcolor: 'rgba(255,255,255,0.95)', backdropFilter: 'blur(10px)',
          borderTop: '1px solid #DDD4F8',
        }}>
          <Button
            fullWidth variant="contained" startIcon={<Edit />}
            onClick={() => navigate(/projects//edit)}
            sx={{ borderRadius: 2, py: 1.2, background: 'linear-gradient(135deg,#6A1FCC,#8E43F0)', fontWeight: 700 }}
          >
            Edit Project
          </Button>
        </Box>
      )}
    </Box>
  );
}
