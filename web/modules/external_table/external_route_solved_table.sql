CREATE TABLE external_route_solved (
  id SERIAL PRIMARY KEY,
  nid INT NOT NULL,
  route_data LONGTEXT NOT NULL,
  created INT NOT NULL,
  changed INT NOT NULL
);