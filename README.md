# Tabby Cat

A two-tier master-detail display component for WordPress. Part of the Cozy Cat family.

## Requirements

- WordPress 5.0+
- Advanced Custom Fields (ACF) Pro or Free

## Installation

1. Upload the `tabby-cat` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Tabby Cat → Settings** to customize labels for your use case

## Configuration

The Settings page allows you to customize:

- **Content Type Labels**: Change "Tabby Cat Item" to whatever fits your project (e.g., "Additional Work", "Team Member", "Product")
- **Category Labels**: Change "Category" to fit your taxonomy (e.g., "Service", "Department", "Type")
- **Menu Icon**: Choose from common dashicons

## Adding Content

1. Go to **Tabby Cat → Categories** and create your top-level categories
2. Go to **Tabby Cat → Add New** to create items
3. Fill in the title, description, optional link, and visual (image, gallery, or video)
4. Assign each item to one or more categories

## Usage

Add the shortcode to any page or post:

```
[tabby_cat]
```

### Shortcode Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| `category` | Only show items from these category slugs (comma-separated) | all |
| `exclude_category` | Exclude items from these category slugs (comma-separated) | none |
| `orderby` | Order by: title, date, menu_order | title |
| `order` | ASC or DESC | ASC |

### Examples

Show only branding and web design work:
```
[tabby_cat category="branding,web-design"]
```

Show all except archived items, newest first:
```
[tabby_cat exclude_category="archived" orderby="date" order="DESC"]
```

## File Structure

```
tabby-cat/
├── tabby-cat.php        # Main plugin file
├── acf-json/            # ACF field definitions (auto-loaded)
│   └── group_tabby_cat_item.json
└── README.md
```

## Changelog

### 1.0.0
- Initial release
- Customizable CPT and taxonomy labels
- Support for image, gallery, and video visuals
- Settings page with icon picker
