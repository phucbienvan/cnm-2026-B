"""Create documents and document chunks."""

from alembic import op

from app.core.config import settings

revision = "1c0d839f75b2"
down_revision = None
branch_labels = None
depends_on = None


def upgrade() -> None:
    dimensions = settings.embedding_dimensions
    if not 1 <= dimensions <= 2000:
        raise ValueError("EMBEDDING_DIMENSIONS must be between 1 and 2000 for the HNSW vector index")

    op.execute("CREATE EXTENSION IF NOT EXISTS vector")
    op.execute("""
        CREATE TABLE documents (
            id uuid PRIMARY KEY,
            filename text NOT NULL,
            content_type text,
            file_hash text NOT NULL UNIQUE,
            storage_path text NOT NULL,
            status text NOT NULL DEFAULT 'processing',
            error_message text,
            chunk_count integer NOT NULL DEFAULT 0,
            created_at timestamptz NOT NULL DEFAULT now()
        )
    """)
    op.execute(f"""
        CREATE TABLE document_chunks (
            id bigserial PRIMARY KEY,
            document_id uuid NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
            chunk_index integer NOT NULL,
            content text NOT NULL,
            page_number integer,
            section_title text,
            metadata jsonb NOT NULL DEFAULT '{{}}'::jsonb,
            embedding vector({dimensions}) NOT NULL,
            search_vector tsvector GENERATED ALWAYS AS (
                to_tsvector('simple', coalesce(section_title, '') || ' ' || content)
            ) STORED,
            UNIQUE (document_id, chunk_index)
        )
    """)
    op.execute("CREATE INDEX chunks_document_id_idx ON document_chunks (document_id)")
    op.execute("CREATE INDEX chunks_search_vector_idx ON document_chunks USING gin (search_vector)")
    op.execute("""
        CREATE INDEX chunks_embedding_hnsw_idx
        ON document_chunks USING hnsw (embedding vector_cosine_ops)
    """)


def downgrade() -> None:
    op.drop_table("document_chunks")
    op.drop_table("documents")
