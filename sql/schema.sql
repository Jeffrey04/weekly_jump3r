CREATE SCHEMA log;

CREATE TABLE "website" (
    id SERIAL,
    url CHARACTER VARYING NOT NULL,
    count INTEGER,
    badge_id INTEGER NOT NULL,
    PRIMARY KEY("id"),
    UNIQUE KEY ("url", "count", "badge_id")
);
