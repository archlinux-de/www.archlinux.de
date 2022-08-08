describe('News Item page', () => {
  beforeEach(() => {
    cy.visit('/news/18784-Das-Canterbury-Projekt')
  })

  it('shows title', () => {
    cy.contains('h1', 'Canterbury')
  })

  it('shows date', () => {
    cy.get('[data-test=news-date]').should('be.visible').invoke('text').should('match', /[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}/)
  })

  it('shows author', () => {
    cy.get('[data-test=news-author]').should('be.visible')
  })

  it('shows content', () => {
    cy.get('[data-test=news-content]').should('be.visible').invoke('text').should('contains', 'Canterbury')
  })

  it('links comments', () => {
    cy.get('[data-test=news-comments-link]').should('have.attr', 'href').and('contain', 'forum.archlinux.de')
  })
})
