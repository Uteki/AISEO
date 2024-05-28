/**
* Implements hook_help().
*/
function custom_seo_generator_help($route_name, RouteMatchInterface $route_match) {
switch ($route_name) {
case 'help.page.custom_seo_generator':
return '<p>' . t('Generate SEO optimized text based on keywords.') . '</p>';
}
}
