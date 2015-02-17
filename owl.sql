PRAGMA encoding = "UTF-8";
CREATE TABLE owl_person_authorityForm (
  id        TEXT NOT NULL,
  label     TEXT NOT NULL,
  comment   TEXT
);
CREATE TABLE owl_person_rejectedForm (
  id        TEXT NOT NULL,
  label     TEXT NOT NULL,
  apf_id    TEXT NOT NULL REFERENCES ontology_authority_person_form(id)
);
CREATE TABLE owl_topic (
  id        TEXT NOT NULL,
  label     TEXT NOT NULL,
  parent    TEXT NOT NULL
);
CREATE TABLE owl_contains (
  article_id  TEXT NOT NULL REFERENCES article(name),
  tag_id      TEXT NOT NULL,
  tag_type    TEXT NOT NULL   -- topic or person
);
-- disposer de tous les tags (person, topic)
CREATE TABLE owl_allTags (
  id        TEXT NOT NULL,
  label     TEXT NOT NULL,
  parent    TEXT NOT NULL,
  type      TEXT NOT NULL
)