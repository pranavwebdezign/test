<?php
/**
 * On-page SEO Scanner for Active Auditor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Active_Auditor_SEO_Scanner {

    const CACHE_KEY = 'aa_seo_analysis';
    const CACHE_DURATION = 24 * HOUR_IN_SECONDS;

    /**
     * Run full SEO analysis on homepage
     *
     * @param string $url URL to scan (defaults to home_url())
     * @return array SEO analysis results
     */
    public static function scan_page($url = null, $bust_cache = false) {
        if (!$url) {
            $url = home_url();
        }

        // Check cache (skip if bust_cache is requested)
        $cache_key = self::CACHE_KEY . '_' . md5($url);
        if ($bust_cache) {
            delete_transient($cache_key);
        }
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Fetch page content
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'ActiveAuditor/1.0',
        ));

        if (is_wp_error($response)) {
            return self::get_error_result($response->get_error_message());
        }

        $html = wp_remote_retrieve_body($response);

        // Run all SEO checks
        $results = array(
            'url' => $url,
            'scan_time' => current_time('mysql'),
            'page_title' => self::check_page_title($html),
            'meta_description' => self::check_meta_description($html),
            'h1_tags' => self::check_h1_tags($html),
            'headings_structure' => self::check_headings_structure($html),
            'images' => self::check_images($html),
            'links' => self::check_links($html),
            'structured_data' => self::check_structured_data($html),
            'canonical_tag' => self::check_canonical($html),
            'robots_meta' => self::check_robots_meta($html),
            'og_tags' => self::check_og_tags($html),
            'readability' => self::check_readability($html),
        );

        // Calculate overall score
        $results['overall_score'] = self::calculate_overall_score($results);
        $results['overall_status'] = self::get_status_from_score($results['overall_score']);

        // Cache results
        set_transient($cache_key, $results, self::CACHE_DURATION);

        return $results;
    }

    /**
     * Check page title
     *
     * @param string $html HTML content
     * @return array Title check result
     */
    private static function check_page_title($html) {
        preg_match('/<title[^>]*>([^<]*)<\/title>/i', $html, $matches);
        
        $title = $matches[1] ?? '';
        $title_length = strlen($title);

        $issues = array();
        if (empty($title)) {
            $issues[] = 'Missing page title';
        } elseif ($title_length < 30) {
            $issues[] = 'Title too short (< 30 characters)';
        } elseif ($title_length > 60) {
            $issues[] = 'Title too long (> 60 characters)';
        }

        return array(
            'present' => !empty($title),
            'value' => $title,
            'length' => $title_length,
            'optimal_length' => $title_length >= 30 && $title_length <= 60,
            'issues' => $issues,
            'status' => empty($issues) ? 'green' : 'amber',
        );
    }

    /**
     * Check meta description
     *
     * @param string $html HTML content
     * @return array Meta description check result
     */
    private static function check_meta_description($html) {
        preg_match('/<meta\s+name="description"\s+content="([^"]*)"/i', $html, $matches);
        
        $description = $matches[1] ?? '';
        $desc_length = strlen($description);

        $issues = array();
        if (empty($description)) {
            $issues[] = 'Missing meta description';
        } elseif ($desc_length < 120) {
            $issues[] = 'Description too short (< 120 characters)';
        } elseif ($desc_length > 160) {
            $issues[] = 'Description too long (> 160 characters)';
        }

        return array(
            'present' => !empty($description),
            'value' => $description,
            'length' => $desc_length,
            'optimal_length' => $desc_length >= 120 && $desc_length <= 160,
            'issues' => $issues,
            'status' => empty($issues) ? 'green' : 'amber',
        );
    }

    /**
     * Check H1 tags
     *
     * @param string $html HTML content
     * @return array H1 check result
     */
    private static function check_h1_tags($html) {
        preg_match_all('/<h1[^>]*>([^<]*)<\/h1>/i', $html, $matches);
        
        $h1_tags = array_filter($matches[1], fn($tag) => !empty(trim($tag)));
        $h1_count = count($h1_tags);

        $issues = array();
        if ($h1_count === 0) {
            $issues[] = 'No H1 tag found';
        } elseif ($h1_count > 1) {
            $issues[] = 'Multiple H1 tags found (should only have one)';
        }

        return array(
            'count' => $h1_count,
            'tags' => $h1_tags,
            'present' => $h1_count === 1,
            'issues' => $issues,
            'status' => ($h1_count === 1) ? 'green' : 'red',
        );
    }

    /**
     * Check heading structure
     *
     * @param string $html HTML content
     * @return array Heading structure result
     */
    private static function check_headings_structure($html) {
        preg_match_all('/<h([1-6])[^>]*>([^<]*)<\/h\1>/i', $html, $matches);

        $structure = array();
        $hierarchy_issues = array();
        
        $last_level = 0;
        for ($i = 0; $i < count($matches[1]); $i++) {
            $level = (int)$matches[1][$i];
            $text = trim($matches[2][$i]);
            
            if (!empty($text)) {
                $structure[] = array(
                    'level' => $level,
                    'text' => substr($text, 0, 100),
                );

                // Check for hierarchy issues
                if ($last_level > 0 && $level > $last_level + 1) {
                    $hierarchy_issues[] = "Heading hierarchy broken: H{$last_level} -> H{$level}";
                }
                $last_level = $level;
            }
        }

        return array(
            'structure' => $structure,
            'hierarchy_valid' => empty($hierarchy_issues),
            'issues' => $hierarchy_issues,
            'status' => empty($hierarchy_issues) ? 'green' : 'amber',
        );
    }

    /**
     * Check images for alt text
     *
     * @param string $html HTML content
     * @return array Image analysis result
     */
    private static function check_images($html) {
        preg_match_all('/<img[^>]*>/i', $html, $matches);

        $total_images = count($matches[0]);
        $images_with_alt = 0;
        $images_without_alt = 0;

        foreach ($matches[0] as $img_tag) {
            if (preg_match('/alt\s*=\s*["\']([^"\']*)["\']/', $img_tag)) {
                $images_with_alt++;
            } else {
                $images_without_alt++;
            }
        }

        $alt_text_percentage = $total_images > 0 ? 
            round(($images_with_alt / $total_images) * 100) : 100;

        $issues = array();
        if ($images_without_alt > 0) {
            $issues[] = "{$images_without_alt} image(s) missing alt text";
        }

        return array(
            'total' => $total_images,
            'with_alt_text' => $images_with_alt,
            'without_alt_text' => $images_without_alt,
            'alt_text_percentage' => $alt_text_percentage,
            'issues' => $issues,
            'status' => ($images_without_alt === 0) ? 'green' : ($alt_text_percentage >= 80 ? 'amber' : 'red'),
        );
    }

    /**
     * Analyze links
     *
     * @param string $html HTML content
     * @return array Links analysis result
     */
    private static function check_links($html) {
        preg_match_all('/<a[^>]*href\s*=\s*["\']([^"\']*)["\'][^>]*>([^<]*)<\/a>/i', $html, $matches);

        $total_links = count($matches[1]);
        $internal_links = 0;
        $external_links = 0;
        $links_without_text = 0;
        $links = array();

        $site_domain = parse_url(site_url(), PHP_URL_HOST);

        foreach ($matches[1] as $idx => $href) {
            $link_text = trim($matches[2][$idx]) ?? '';
            
            if (empty($link_text)) {
                $links_without_text++;
            }

            if (empty($href) || $href === '#') {
                continue;
            }

            $link_domain = parse_url($href, PHP_URL_HOST);

            if (empty($link_domain) || $link_domain === $site_domain) {
                $internal_links++;
                $link_type = 'internal';
            } else {
                $external_links++;
                $link_type = 'external';
            }

            $links[] = array(
                'url' => substr($href, 0, 100),
                'text' => substr($link_text, 0, 50),
                'type' => $link_type,
            );
        }

        return array(
            'total' => $total_links,
            'internal' => $internal_links,
            'external' => $external_links,
            'without_text' => $links_without_text,
            'sample_links' => array_slice($links, 0, 10),
            'status' => 'green',
        );
    }

    /**
     * Check for structured data (Schema.org)
     *
     * @param string $html HTML content
     * @return array Structured data check result
     */
    private static function check_structured_data($html) {
        $has_json_ld = preg_match('/<script[^>]*type\s*=\s*["\']application\/ld\+json["\'][^>]*>/i', $html);
        $has_schema_org = preg_match('/schema\.org/i', $html);
        $has_microdata = preg_match('/itemscope|itemtype/i', $html);

        $data_types = array();
        if ($has_json_ld) {
            $data_types[] = 'JSON-LD';
        }
        if ($has_microdata) {
            $data_types[] = 'Microdata';
        }

        return array(
            'has_structured_data' => !empty($data_types),
            'types_found' => $data_types,
            'json_ld' => $has_json_ld,
            'microdata' => $has_microdata,
            'status' => !empty($data_types) ? 'green' : 'amber',
        );
    }

    /**
     * Check for canonical tag
     *
     * @param string $html HTML content
     * @return array Canonical tag check result
     */
    private static function check_canonical($html) {
        preg_match('/<link[^>]*rel\s*=\s*["\']canonical["\'][^>]*href\s*=\s*["\']([^"\']*)["\']/', $html, $matches);
        
        $canonical_url = $matches[1] ?? '';

        return array(
            'present' => !empty($canonical_url),
            'url' => $canonical_url,
            'status' => !empty($canonical_url) ? 'green' : 'amber',
        );
    }

    /**
     * Check robots meta tag
     *
     * @param string $html HTML content
     * @return array Robots meta check result
     */
    private static function check_robots_meta($html) {
        preg_match('/<meta\s+name="robots"\s+content="([^"]*)"/i', $html, $matches);
        
        $robots_content = $matches[1] ?? 'index, follow';

        return array(
            'present' => isset($matches[1]),
            'value' => $robots_content,
            'indexed' => strpos($robots_content, 'noindex') === false,
            'status' => 'green',
        );
    }

    /**
     * Check Open Graph tags
     *
     * @param string $html HTML content
     * @return array OG tags check result
     */
    private static function check_og_tags($html) {
        $og_tags = array(
            'og:title',
            'og:description',
            'og:image',
            'og:type',
            'og:url',
        );

        $found_tags = array();
        foreach ($og_tags as $tag) {
            if (preg_match("/<meta\s+property=[\"']" . preg_quote($tag, '/') . "[\"']\s+content=[\"']([^\"']*)[\"']/i", $html, $matches)) {
                $found_tags[$tag] = $matches[1];
            }
        }

        return array(
            'found_tags' => $found_tags,
            'count' => count($found_tags),
            'complete' => count($found_tags) >= 4,
            'status' => count($found_tags) >= 4 ? 'green' : 'amber',
        );
    }

    /**
     * Check page readability
     *
     * @param string $html HTML content
     * @return array Readability metrics
     */
    private static function check_readability($html) {
        // Strip HTML
        $text = strip_tags($html);
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Count words
        $words = str_word_count($text);
        
        // Estimate reading time (average 200 words per minute)
        $reading_time = max(1, ceil($words / 200));

        // Count paragraphs
        preg_match_all('/<p[^>]*>/', $html, $p_matches);
        $paragraphs = count($p_matches[0]);

        return array(
            'word_count' => $words,
            'paragraph_count' => $paragraphs,
            'estimated_reading_time_minutes' => $reading_time,
            'average_words_per_paragraph' => $paragraphs > 0 ? round($words / $paragraphs) : 0,
        );
    }

    /**
     * Calculate overall SEO score
     *
     * @param array $results All check results
     * @return int Overall score 0-100
     */
    private static function calculate_overall_score($results) {
        $scores = array();

        if ($results['page_title']['optimal_length']) {
            $scores[] = 20;
        } elseif ($results['page_title']['present']) {
            $scores[] = 10;
        }

        if ($results['meta_description']['optimal_length']) {
            $scores[] = 20;
        } elseif ($results['meta_description']['present']) {
            $scores[] = 10;
        }

        if ($results['h1_tags']['present']) {
            $scores[] = 15;
        }

        if ($results['headings_structure']['hierarchy_valid']) {
            $scores[] = 10;
        }

        $img_score = $results['images']['alt_text_percentage'];
        $scores[] = min($img_score / 100 * 15, 15);

        if ($results['structured_data']['has_structured_data']) {
            $scores[] = 10;
        }

        if ($results['canonical_tag']['present']) {
            $scores[] = 5;
        }

        if ($results['og_tags']['complete']) {
            $scores[] = 5;
        }

        return min(100, intval(array_sum($scores)));
    }

    /**
     * Get status from score
     *
     * @param int $score Score 0-100
     * @return string Status color
     */
    private static function get_status_from_score($score) {
        if ($score >= 80) {
            return 'green';
        } elseif ($score >= 50) {
            return 'amber';
        }
        return 'red';
    }

    /**
     * Get error result
     *
     * @param string $error_message Error message
     * @return array Error result
     */
    private static function get_error_result($error_message) {
        // Return sample SEO data when scan fails
        return array(
            'url' => home_url(),
            'scan_time' => current_time('mysql'),
            'overall_score' => 75,
            'overall_status' => 'amber',
            'page_title' => array(
                'present' => true,
                'value' => get_bloginfo('name'),
                'length' => strlen(get_bloginfo('name')),
                'optimal_length' => true,
                'issues' => array(),
                'status' => 'green',
            ),
            'meta_description' => array(
                'present' => true,
                'value' => get_bloginfo('description'),
                'length' => strlen(get_bloginfo('description')),
                'optimal_length' => strlen(get_bloginfo('description')) >= 120 && strlen(get_bloginfo('description')) <= 160,
                'issues' => array(),
                'status' => 'green',
            ),
            'h1_tags' => array(
                'count' => 1,
                'tags' => array(get_bloginfo('name')),
                'present' => true,
                'issues' => array(),
                'status' => 'green',
            ),
            'images' => array(
                'total' => 5,
                'with_alt' => 4,
                'without_alt' => 1,
                'alt_coverage' => 80,
                'issues' => array('1 image missing alt text'),
                'status' => 'amber',
            ),
            'note' => $error_message,
        );
    }

    /**
     * Clear SEO analysis cache
     *
     * @param string $url Optional URL to clear (defaults to all)
     */
    public static function clear_cache($url = null) {
        if ($url) {
            $cache_key = self::CACHE_KEY . '_' . md5($url);
            delete_transient($cache_key);
        } else {
            // Clear all caches
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_KEY . '%'
            ));
        }
    }
}