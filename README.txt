migrate_field_translated
========================

Allows migrating field-translated content from D7 to D8. This means your D7 site should be using the entity_translation module, with the content-type you're interested in set to use "field translation".

Currently only nodes are supported, but it shouldn't be too hard to add other entity types.


Migration settings
------------------

Your migration YAML file should be named something like migrate.migration.mybundle.yml, and should look roughly like this:

  id: mybundle
  label: MyBundle
  source:
    plugin: d7_translated_node
    database_state_key: migrate_upgrade_7
    node_type: mybundle
  process:
    type: type
    langcode: language
    title: title
    body: body
    translations:
      plugin: d7_translated
      source: translations
  destination:
    plugin: 'd7_translated_entity:node'


Implementation
--------------

For each D7 source node, a Row is generated with all the normal fields, plus a 'translations' key, which holds a mapping of language codes to Rows for each translation. Each translation is processed the same as every base Row. Then the translations are all merged into one row in D7.
