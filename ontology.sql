PRAGMA encoding = "UTF-8";
CREATE TABLE ontology_authority_person_form (
  id        TEXT NOT NULL,
  label     TEXT NOT NULL,
  comment   TEXT
);
CREATE TABLE ontology_rejected_person_form (
  id        TEXT NOT NULL,
  label     TEXT NOT NULL,
  apf_id    TEXT NOT NULL REFERENCES ontology_authority_person_form(id)
);
CREATE TABLE ontology_topic (
  id        TEXT NOT NULL,
  label     TEXT NOT NULL,
  parent    TEXT NOT NULL
);
CREATE TABLE ontology_contains (
  article_id      TEXT NOT NULL REFERENCES article(name),
  indexentry_id   TEXT NOT NULL,
  indexentry_type TEXT NOT NULL   -- topic or person
)