import { useState } from 'react';
import {
  Box, Typography, Button, Chip, Grid,
  Table, TableBody, TableCell, TableContainer,
  TableHead, TableRow, Paper, TablePagination,
  Card, CardContent,
} from '@mui/material';
import {
  Security as SecurityIcon,
  Warning,
  Error as ErrorIcon,
  CheckCircle,
} from '@mui/icons-material';
import { formatDistanceToNow } from 'date-fns';
import { useQuery } from '@apollo/client';
import AppLayout from '../components/layout/AppLayout';
import { GET_WP_FINDINGS, GET_WP_SITES } from '../graphql/queries';

const severityColors = {
  high: 'error',
  medium: 'warning',
  low: 'info',
};

const typeLabels = {
  xml_rpc: 'XML-RPC',
  user_enumeration: 'User Enumeration',
  outdated_php: 'Outdated PHP',
  weak_passwords: 'Weak Passwords',
  exposed_debug: 'Debug Exposed',
};

function StatCard({ title, value, color, icon }) {
  return (
    <Card variant="outlined" sx={{ height: '100%' }}>
      <CardContent>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1 }}>
          {icon}
          <Typography variant="body2" color="text.secondary">{title}</Typography>
        </Box>
        <Typography variant="h4" fontWeight={700} color={color}>{value}</Typography>
      </CardContent>
    </Card>
  );
}

export default function Security() {
  const [page, setPage] = useState(0);
  const [rowsPerPage] = useState(10);

  const { data: findingsData } = useQuery(GET_WP_FINDINGS, { fetchPolicy: 'cache-and-network' });
  const { data: sitesData } = useQuery(GET_WP_SITES, { fetchPolicy: 'cache-and-network' });

  const securityIssues = findingsData?.wpFindings || [];
  const sites = sitesData?.wpSites || [];

  const highCount = securityIssues.filter((i) => i.severity === 'high').length;
  const mediumCount = securityIssues.filter((i) => i.severity === 'medium').length;
  const lowCount = securityIssues.filter((i) => i.severity === 'low').length;
  const secureCount = sites.filter((s) => !securityIssues.some((i) => i.site?.id === s.id)).length;

  const paginated = securityIssues.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage);

  return (
    <AppLayout>
      <Box sx={{ p: { xs: 2, md: 3 } }}>
        {/* Header */}
        <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h4" fontWeight={600}>Security</Typography>
            <Typography variant="body2" color="text.secondary">Security posture overview across all sites</Typography>
          </Box>
          <Button variant="contained" startIcon={<SecurityIcon />}>Run Security Scan</Button>
        </Box>

        {/* Stats */}
        <Grid container spacing={2} sx={{ mb: 3 }}>
          <Grid item xs={12} sm={6} lg={3}>
            <StatCard title="Secure Sites" value={secureCount} color="success.main" icon={<CheckCircle color="success" />} />
          </Grid>
          <Grid item xs={12} sm={6} lg={3}>
            <StatCard title="High Severity" value={highCount} color="error.main" icon={<ErrorIcon color="error" />} />
          </Grid>
          <Grid item xs={12} sm={6} lg={3}>
            <StatCard title="Medium Severity" value={mediumCount} color="warning.main" icon={<Warning color="warning" />} />
          </Grid>
          <Grid item xs={12} sm={6} lg={3}>
            <StatCard title="Low Severity" value={lowCount} color="info.main" icon={<SecurityIcon color="info" />} />
          </Grid>
        </Grid>

        {/* Table */}
        <Paper variant="outlined">
          <Box sx={{ px: 2, py: 1.5, borderBottom: '1px solid', borderColor: 'divider', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Typography variant="subtitle1" fontWeight={600}>Security Issues</Typography>
            <Typography variant="body2" color="text.secondary">{securityIssues.length} total issues</Typography>
          </Box>
          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow sx={{ background: '#fafafa' }}>
                  <TableCell><strong>Severity</strong></TableCell>
                  <TableCell><strong>Issue</strong></TableCell>
                  <TableCell><strong>Site</strong></TableCell>
                  <TableCell><strong>Type</strong></TableCell>
                  <TableCell><strong>Detected</strong></TableCell>
                  <TableCell />
                </TableRow>
              </TableHead>
              <TableBody>
                {paginated.map((row) => (
                  <TableRow key={row.id} hover>
                    <TableCell>
                      <Chip label={row.severity.toUpperCase()} color={severityColors[row.severity]} size="small" sx={{ fontWeight: 600 }} />
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2" fontWeight={600}>{row.title}</Typography>
                      <Typography variant="caption" color="text.secondary">{row.description}</Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">{row.site?.name || 'Unknown Site'}</Typography>
                    </TableCell>
                    <TableCell>
                      <Chip label={typeLabels[row.check_type] || row.check_type || 'Unknown'} size="small" variant="outlined" />
                    </TableCell>
                    <TableCell>
                      <Typography variant="caption" color="text.secondary">
                        {row.detected_at ? formatDistanceToNow(new Date(row.detected_at), { addSuffix: true }) : '—'}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Button size="small">View</Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
          <TablePagination
            component="div"
            count={securityIssues.length}
            page={page}
            onPageChange={(_, p) => setPage(p)}
            rowsPerPage={rowsPerPage}
            rowsPerPageOptions={[10]}
          />
        </Paper>
      </Box>
    </AppLayout>
  );
}
