import { Box, Card, CardContent, Divider, Typography } from '@mui/material';

/**
 * Shared section card used by EditSite and AddSite.
 * Renders a card with a purple icon, bold title, and divider.
 */
export default function SectionCard({ icon: Icon, title, children }) {
    return (
        <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
            <CardContent sx={{ p: 3 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 2.5 }}>
                    <Box sx={{
                        width: 32, height: 32, borderRadius: 1.5,
                        bgcolor: '#EDE8FC', display: 'flex', alignItems: 'center', justifyContent: 'center',
                    }}>
                        <Icon sx={{ fontSize: 18, color: '#8E43F0' }} />
                    </Box>
                    <Typography variant="subtitle1" fontWeight={700}>{title}</Typography>
                </Box>
                <Divider sx={{ mb: 2.5 }} />
                {children}
            </CardContent>
        </Card>
    );
}
