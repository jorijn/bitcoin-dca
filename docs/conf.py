import sphinx_rtd_theme

project = 'BL3P-DCA'
copyright = '2020, Jorijn Schrijvershof'
author = 'Jorijn Schrijvershof'
extensions = []
templates_path = ['_templates']
exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store']
html_theme = 'sphinx_rtd_theme'
html_static_path = ['_static']
pygments_style = 'sphinx'
html_theme_path = [sphinx_rtd_theme.get_html_theme_path()]
html_theme_options = {
    'navigation_depth': 4,
}
