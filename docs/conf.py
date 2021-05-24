import sphinx_rtd_theme

project = 'Bitcoin DCA'
copyright = '2021, Jorijn Schrijvershof'
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
master_doc = 'index'
html_logo = '../resources/images/logo-white.png'

# I use a privacy focussed service https://usefathom.com/ to track how the documentation
# is being used. This allows me to improve its contents.
html_js_files = [('https://krill.jorijn.com/script.js', {'data-site': 'MXGDAIWO'})]
